<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    /*
    |--------------------------------------------------------------------------
    | 邮件服务配置
    |--------------------------------------------------------------------------
    |
    | 这里配置邮件服务的相关设置。
    |
    */
    'default_mailer' => env('MAIL_MAILER', 'smtp'),
    'from'           => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name'    => env('MAIL_FROM_NAME', 'Example'),
    ],
    'mailers' => [
        'smtp' => [
            'transport'  => 'smtp',
            'host'       => env('MAIL_HOST', 'localhost'),
            'port'       => (int) env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username'   => env('MAIL_USERNAME'),
            'password'   => env('MAIL_PASSWORD'),
        ],
    ],
];
