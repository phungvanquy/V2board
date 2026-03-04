<?php

namespace App\Utils;

class CacheKey
{
    CONST KEYS = [
        'EMAIL_VERIFY_CODE' => 'Email verification code',
        'LAST_SEND_EMAIL_VERIFY_TIMESTAMP' => 'Last email verification code sent time',
        'SERVER_VMESS_ONLINE_USER' => 'Vmess server online users',
        'SERVER_VMESS_LAST_CHECK_AT' => 'Vmess server last check time',
        'SERVER_VMESS_LAST_PUSH_AT' => 'Vmess server last push time',
        'SERVER_TROJAN_ONLINE_USER' => 'Trojan server online users',
        'SERVER_TROJAN_LAST_CHECK_AT' => 'Trojan server last check time',
        'SERVER_TROJAN_LAST_PUSH_AT' => 'Trojan server last push time',
        'SERVER_SHADOWSOCKS_ONLINE_USER' => 'Shadowsocks server online users',
        'SERVER_SHADOWSOCKS_LAST_CHECK_AT' => 'Shadowsocks server last check time',
        'SERVER_SHADOWSOCKS_LAST_PUSH_AT' => 'Shadowsocks server last push time',
        'SERVER_HYSTERIA_ONLINE_USER' => 'Hysteria server online users',
        'SERVER_HYSTERIA_LAST_CHECK_AT' => 'Hysteria server last check time',
        'SERVER_HYSTERIA_LAST_PUSH_AT' => 'Hysteria server last push time',
        'SERVER_VLESS_ONLINE_USER' => 'Vless server online users',
        'SERVER_VLESS_LAST_CHECK_AT' => 'Vless server last check time',
        'SERVER_VLESS_LAST_PUSH_AT' => 'Vless server last push time',
        'TEMP_TOKEN' => 'Temporary token',
        'LAST_SEND_EMAIL_REMIND_TRAFFIC' => 'Last traffic reminder email sent',
        'SCHEDULE_LAST_CHECK_AT' => 'Schedule last check time',
        'REGISTER_IP_RATE_LIMIT' => 'Registration rate limit',
        'LAST_SEND_LOGIN_WITH_MAIL_LINK_TIMESTAMP' => 'Last login link email sent time',
        'PASSWORD_ERROR_LIMIT' => 'Password error limit',
        'USER_SESSIONS' => 'User sessions',
        'FORGET_REQUEST_LIMIT' => 'Password reset request limit'
    ];

    public static function get(string $key, $uniqueValue)
    {
        if (!in_array($key, array_keys(self::KEYS))) {
            abort(500, 'key is not in cache key list');
        }
        return $key . '_' . $uniqueValue;
    }
}
