<?php

use function Hyperf\Support\env;

return [
    /*
    |--------------------------------------------------------------------------
    | 默认通知渠道
    |--------------------------------------------------------------------------
    |
    | 此选项控制通知的默认发送渠道。
    |
    */
    'default' => env('NOTIFICATION_DEFAULT_CHANNEL', 'database'),

    /*
    |--------------------------------------------------------------------------
    | 通知渠道
    |--------------------------------------------------------------------------
    |
    | 这里配置可用的通知渠道。
    |
    */
    'channels' => [
        'mail' => [
            'driver' => 'mail',
            'queue' => env('NOTIFICATION_MAIL_QUEUE', 'default'),
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'notifications',
            'queue' => env('NOTIFICATION_DATABASE_QUEUE', 'default'),
        ],
        'broadcast' => [
            'driver' => 'broadcast',
            'queue' => env('NOTIFICATION_BROADCAST_QUEUE', 'default'),
        ],
        'slack' => [
            'driver' => 'slack',
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'queue' => env('NOTIFICATION_SLACK_QUEUE', 'default'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知队列配置
    |--------------------------------------------------------------------------
    |
    | 这里配置通知队列的相关设置。
    |
    */
    'queue' => [
        'connection' => env('NOTIFICATION_QUEUE_CONNECTION', 'default'),
        'queue' => env('NOTIFICATION_QUEUE', 'notifications'),
    ],
];
