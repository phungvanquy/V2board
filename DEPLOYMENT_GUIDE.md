# V2Board Docker Deployment Guide

## Prerequisites

- Docker 20.10+
- Docker Compose 2.0+

## Project Structure

```
v2board/
├── Dockerfile                      # Build image from source
├── docker-compose.yml              # Build mode (builds image + runs)
├── docker-compose.prebuilt.yml     # Prebuilt mode (uses existing image, no rebuild)
├── docker/
│   ├── nginx.conf                  # Nginx site config
│   ├── supervisord.conf            # Runs php-fpm, nginx, horizon
│   └── entrypoint.sh               # Fixes bind-mount permissions on startup
├── config/v2board.php              # App config (bind-mounted, writable by app)
├── .env                            # Environment variables (bind-mounted, writable by app)
├── backup/                         # Place .sql files here for DB restore
│   └── backup.sql
└── public/assets/admin/            # Admin frontend (bind-mounted)
```

## Key Notes

- The admin panel is accessible at `/admin` by default.
- `config/v2board.php` and `.env` are bind-mounted into the container. The entrypoint script automatically fixes their ownership to `www-data` so the app can write to them.
- MySQL and Redis are only exposed within the Docker network in `docker-compose.prebuilt.yml` (production). The build-mode `docker-compose.yml` exposes them to the host for debugging.
- The MySQL healthcheck includes credentials to avoid `Access denied` log spam.

---

## Option A: Fresh Deployment (No Existing Data)

Use this when setting up V2Board for the first time.

### 1. Configure .env

```bash
cp .env.example .env
```

Edit `.env`:

```env
APP_KEY=
APP_URL=http://your-domain-or-ip

DB_HOST=mysql
DB_DATABASE=v2board
DB_USERNAME=v2board
DB_PASSWORD=v2board_db_pass

REDIS_HOST=redis
```

`DB_HOST=mysql` and `REDIS_HOST=redis` must match the Docker service names.

### 2. Build and start

```bash
docker compose build
docker compose up -d
```

### 3. Initialize V2Board

```bash
# Generate app key
docker compose exec v2board php artisan key:generate

# Run installer (imports schema, creates admin account)
docker compose exec v2board php artisan v2board:install
```

The installer will prompt for admin email and generate a password. Since `.env` already has the DB config, it will skip the database prompts and go straight to schema import.

### 4. Cache config

```bash
docker compose exec v2board php artisan config:cache
```

### 5. Verify

```bash
docker compose ps
```

Open `http://your-server-ip/admin` and log in with the admin credentials from step 3.

### 6. (Optional) Switch to prebuilt mode

After the first build, you can skip rebuilds on future restarts:

```bash
docker compose down
docker compose -f docker-compose.prebuilt.yml up -d
```

---

## Option B: Deploy with Existing Database (SQL File)

Use this when migrating from another server or restoring a backup.

### 1. Place the SQL file

Put your `.sql` backup in the `backup/` directory:

```bash
ls backup/
# backup.sql
```

MySQL will auto-import all `.sql` files from this directory on first start (when the data volume is empty).

### 2. Configure .env

Copy and edit `.env` the same as Option A step 1. Make sure `APP_KEY` matches the original deployment if you're restoring — otherwise existing encrypted data (passwords, tokens) won't decrypt.

### 3. Configure config/v2board.php

If you have the original `config/v2board.php`, copy it into place. This preserves your `secure_path`, payment settings, and other app config.

If not, the default is fine:

```php
<?php
 return array (
  'secure_path' => 'admin',
) ;
```

### 4. Start containers

Build mode (first time):

```bash
docker compose build
docker compose up -d
```

Or prebuilt mode (if image already exists):

```bash
docker compose -f docker-compose.prebuilt.yml up -d
```

### 5. Wait for DB import

The SQL file import happens automatically on first MySQL start. Monitor progress:

```bash
docker compose logs -f mysql
```

Wait until you see `ready for connections`. For large databases this may take a few minutes.

### 6. Verify the database

```bash
docker compose exec mysql mysql -u v2board -pv2board_db_pass v2board -e "SHOW TABLES;"
```

You should see tables like `v2_user`, `v2_plan`, `v2_order`, etc.

### 7. Cache config and restart horizon

```bash
docker compose exec v2board php artisan config:cache
```

### 8. Verify

Open `http://your-server-ip/admin` (or your custom `secure_path`) and log in.

---

## Troubleshooting

### Check logs

```bash
docker compose logs v2board    # App (php-fpm, nginx, horizon)
docker compose logs mysql      # Database
docker compose logs redis      # Cache/queue
```

### Config save fails ("Request failed" / 500 error)

The entrypoint script should auto-fix bind-mount permissions. If it's still failing:

```bash
docker exec v2board-app chown www-data:www-data /var/www/v2board/.env /var/www/v2board/config/v2board.php
```

### Database connection refused

- Verify `DB_HOST=mysql` in `.env` (not `localhost`)
- Check MySQL is healthy: `docker compose ps`
- Credentials in `.env` must match `docker-compose.yml`

### Redis connection refused

- Verify `REDIS_HOST=redis` in `.env` (not `127.0.0.1`)

### Re-import database (reset existing data)

```bash
docker compose down
docker volume rm v2board_mysql-data
docker compose up -d
# MySQL will re-import from backup/ on fresh start
```

