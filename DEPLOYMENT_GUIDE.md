# V2Board Docker Deployment Guide

## Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- Git (chỉ cần cho Option A - build từ source)

## Cấu trúc project

```
v2board/
├── Dockerfile                      # Build image từ source
├── .dockerignore                   # Loại trừ file không cần thiết khi build
├── docker-compose.yml              # Build mode (build image + chạy, expose ports cho debug)
├── docker-compose.prebuilt.yml     # Prebuilt mode (dùng image có sẵn, production)
├── docker/
│   ├── nginx.conf                  # Nginx site config
│   ├── supervisord.conf            # Chạy php-fpm, nginx, horizon
│   └── entrypoint.sh               # Fix quyền bind-mount khi khởi động
├── config/v2board.php              # App config (bind-mount, app ghi được)
├── .env                            # Biến môi trường (bind-mount, app ghi được)
├── .env.example                    # Template .env
├── backup/                         # Đặt file .sql vào đây để restore DB
│   └── backup.sql
├── database/
│   └── install.sql                 # Schema gốc cho cài mới
└── public/assets/admin/            # Admin frontend (bind-mount)
```

## Lưu ý quan trọng

- Admin panel mặc định truy cập tại `/<secure_path>` (mặc định là `/admin`).
- `.env` và `config/v2board.php` được bind-mount vào container. Entrypoint script tự động fix ownership sang `www-data` để app ghi được.
- MySQL và Redis chỉ expose trong Docker network ở `docker-compose.prebuilt.yml` (production). File `docker-compose.yml` expose ra host để debug.
- Healthcheck MySQL dùng credentials để tránh log spam `Access denied`.
- **Lệnh `php artisan v2board:install` KHÔNG dùng được trong Docker** vì nó yêu cầu `.env` chưa tồn tại, nhưng Docker bind-mount `.env` vào container. Thay vào đó, ta cấu hình `.env` thủ công và import schema qua MySQL.

---

## Option A: Build từ source + Cài mới (Fresh Install)

Dùng khi cài V2Board lần đầu, build image từ source code.

### 1. Cấu hình .env

```bash
cp .env.example .env
```

Sửa `.env`:

```env
APP_KEY=
APP_URL=http://your-domain-or-ip

DB_HOST=mysql
DB_DATABASE=v2board
DB_USERNAME=v2board
DB_PASSWORD=v2board_db_pass

REDIS_HOST=redis
```

`DB_HOST=mysql` và `REDIS_HOST=redis` phải khớp với tên service trong docker-compose.

### 2. Build và khởi động

```bash
docker compose build
docker compose up -d
```

### 3. Khởi tạo V2Board

```bash
# Tạo APP_KEY
docker compose exec v2board php artisan key:generate

# Import database schema
docker compose exec v2board php artisan migrate 2>/dev/null || \
  docker compose exec mysql mysql -u v2board -pv2board_db_pass v2board < database/install.sql

# Tạo tài khoản admin (thay email và password theo ý bạn)
docker compose exec v2board php artisan tinker --execute="
  \$user = new \App\Models\User();
  \$user->email = 'admin@example.com';
  \$user->password = password_hash('your-password-here', PASSWORD_DEFAULT);
  \$user->uuid = \App\Utils\Helper::guid(true);
  \$user->token = \App\Utils\Helper::guid();
  \$user->is_admin = 1;
  \$user->save();
  echo 'Admin created successfully';
"
```

### 4. Cache config

```bash
docker compose exec v2board php artisan config:cache
```

### 5. Kiểm tra

```bash
docker compose ps
```

Mở `http://your-server-ip/admin` và đăng nhập bằng tài khoản admin vừa tạo ở bước 3.

### 6. (Tùy chọn) Xuất image để dùng prebuilt mode

Sau khi build xong, image đã được tag là `phungvanquy/v2board:v1.0`. Có thể chuyển sang prebuilt mode:

```bash
docker compose down
docker compose -f docker-compose.prebuilt.yml up -d
```

---

## Option B: Deploy bằng Prebuilt Image (Đóng gói sẵn)

Dùng khi muốn deploy nhanh trên server khác mà không cần build lại. Cách này tiện nhất cho production.

### 1. Đóng gói image (trên máy build)

Sau khi build xong ở Option A, xuất image ra file:

```bash
# Xuất image v2board
docker save phungvanquy/v2board:v1.0 | gzip > v2board-image.tar.gz

# Chuẩn bị thư mục deploy
mkdir -p v2board-deploy/backup v2board-deploy/config v2board-deploy/public/assets
cp docker-compose.prebuilt.yml v2board-deploy/docker-compose.yml
cp .env.example v2board-deploy/.env
cp config/v2board.php v2board-deploy/config/v2board.php
cp -r public/assets/admin v2board-deploy/public/assets/admin
cp public/favicon.ico v2board-deploy/public/favicon.ico
cp database/install.sql v2board-deploy/backup/install.sql
mv v2board-image.tar.gz v2board-deploy/

# Đóng gói tất cả
tar czf v2board-deploy.tar.gz v2board-deploy/
```

Kết quả: file `v2board-deploy.tar.gz` chứa mọi thứ cần thiết.

### 2. Deploy trên server mới

```bash
# Giải nén
tar xzf v2board-deploy.tar.gz
cd v2board-deploy

# Load image
docker load < v2board-image.tar.gz
```

### 3. Cấu hình .env

Sửa `.env`:

```env
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_URL=http://your-domain-or-ip

DB_HOST=mysql
DB_DATABASE=v2board
DB_USERNAME=v2board
DB_PASSWORD=v2board_db_pass

REDIS_HOST=redis
```

Nếu cài mới, để `APP_KEY=` trống rồi generate sau. Nếu migrate từ server cũ, giữ nguyên `APP_KEY` gốc.

### 4. Khởi động

```bash
docker compose up -d
```

MySQL sẽ tự import file `.sql` từ thư mục `backup/` khi khởi động lần đầu (volume trống).

### 5. Khởi tạo (chỉ khi cài mới)

```bash
# Đợi MySQL sẵn sàng
docker compose logs -f mysql
# Đợi đến khi thấy "ready for connections"

# Generate APP_KEY (nếu chưa có)
docker compose exec v2board php artisan key:generate

# Tạo admin
docker compose exec v2board php artisan tinker --execute="
  \$user = new \App\Models\User();
  \$user->email = 'admin@example.com';
  \$user->password = password_hash('your-password-here', PASSWORD_DEFAULT);
  \$user->uuid = \App\Utils\Helper::guid(true);
  \$user->token = \App\Utils\Helper::guid();
  \$user->is_admin = 1;
  \$user->save();
  echo 'Admin created successfully';
"

# Cache config
docker compose exec v2board php artisan config:cache
```

### 6. Kiểm tra

```bash
docker compose ps
```

Mở `http://your-server-ip/admin` và đăng nhập.

---

## Option C: Migrate từ server cũ (Restore backup)

Dùng khi chuyển V2Board từ server cũ sang Docker.

### 1. Chuẩn bị backup

Trên server cũ, export database:

```bash
mysqldump -u root -p v2board > backup.sql
```

Copy các file cần thiết:
- `backup.sql` → `backup/backup.sql`
- `.env` gốc (giữ nguyên `APP_KEY`)
- `config/v2board.php` gốc

### 2. Đặt file vào đúng vị trí

```bash
cp backup.sql backup/backup.sql
cp old-server/.env .env
cp old-server/config/v2board.php config/v2board.php
```

### 3. Sửa .env cho Docker

Chỉ cần đổi host:

```env
DB_HOST=mysql          # đổi từ localhost/127.0.0.1
REDIS_HOST=redis       # đổi từ 127.0.0.1
```

Credentials trong `.env` phải khớp với `docker-compose.yml` (hoặc sửa docker-compose cho khớp).

### 4. Khởi động

Build mode:

```bash
docker compose build
docker compose up -d
```

Hoặc prebuilt mode (nếu đã có image):

```bash
docker compose -f docker-compose.prebuilt.yml up -d
```

### 5. Đợi DB import

```bash
docker compose logs -f mysql
```

Đợi đến khi thấy `ready for connections`.

### 6. Kiểm tra database

```bash
docker compose exec mysql mysql -u v2board -pv2board_db_pass v2board -e "SHOW TABLES;"
```

Phải thấy các bảng như `v2_user`, `v2_plan`, `v2_order`, v.v.

### 7. Cache config

```bash
docker compose exec v2board php artisan config:cache
```

### 8. Kiểm tra

Mở `http://your-server-ip/<secure_path>` và đăng nhập. `secure_path` lấy từ `config/v2board.php`.

---

## Troubleshooting

### Xem logs

```bash
docker compose logs v2board    # App (php-fpm, nginx, horizon)
docker compose logs mysql      # Database
docker compose logs redis      # Cache/queue
```

### Lưu config bị lỗi ("Request failed" / 500)

Entrypoint script tự fix quyền bind-mount. Nếu vẫn lỗi:

```bash
docker exec v2board-app chown www-data:www-data /var/www/v2board/.env /var/www/v2board/config/v2board.php
```

### Database connection refused

- Kiểm tra `DB_HOST=mysql` trong `.env` (không phải `localhost`)
- Kiểm tra MySQL healthy: `docker compose ps`
- Credentials trong `.env` phải khớp với `docker-compose.yml`

### Redis connection refused

- Kiểm tra `REDIS_HOST=redis` trong `.env` (không phải `127.0.0.1`)

### Re-import database (xóa data cũ)

```bash
docker compose down
docker volume rm v2board_mysql-data   # hoặc tên volume tương ứng
docker compose up -d
# MySQL sẽ import lại từ backup/ khi khởi động fresh
```

### Horizon không chạy

```bash
docker compose exec v2board supervisorctl status
docker compose exec v2board supervisorctl restart horizon
```

### Xem tất cả volumes

```bash
docker volume ls | grep v2board
```

