<?php

return [
    'cookie_name' => env('SECURE_SESSION_COOKIE', 'hirely_session_token'),
    'lifetime_minutes' => (int) env('SECURE_SESSION_LIFETIME_MINUTES', 1440),
    'bind_ip' => filter_var(env('SECURE_SESSION_BIND_IP', true), FILTER_VALIDATE_BOOL),
    'bind_user_agent' => filter_var(env('SECURE_SESSION_BIND_USER_AGENT', true), FILTER_VALIDATE_BOOL),
    'cookie_secure' => filter_var(env('SECURE_SESSION_COOKIE_SECURE', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),
    'same_site' => env('SECURE_SESSION_SAME_SITE', 'lax'),
];
