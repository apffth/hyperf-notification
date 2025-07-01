<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [


    /*
    |--------------------------------------------------------------------------
    | 通知队列配置
    |--------------------------------------------------------------------------
    |
    | 这里配置通知队列的相关设置。
    |
    */
    'queue' => [
        'queue' => env('NOTIFICATION_QUEUE', 'notification'),
        'delay' => (int) env('NOTIFICATION_QUEUE_DELAY', 0),
        'tries' => (int) env('NOTIFICATION_QUEUE_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知事件配置
    |--------------------------------------------------------------------------
    |
    | 这里配置通知事件的相关设置。
    |
    */
    'events' => [
        // 是否启用事件系统
        'enabled' => env('NOTIFICATION_EVENTS_ENABLED', true),

        // 是否启用发送前事件
        'enable_sending_event' => env('NOTIFICATION_ENABLE_SENDING_EVENT', true),

        // 是否启用发送后事件
        'enable_sent_event' => env('NOTIFICATION_ENABLE_SENT_EVENT', true),

        // 是否启用失败事件
        'enable_failed_event' => env('NOTIFICATION_ENABLE_FAILED_EVENT', true),

        // 是否记录事件日志
        'log_events' => env('NOTIFICATION_LOG_EVENTS', true),

        // 事件监听器配置
        'listeners' => [
            // 可以在这里配置全局事件监听器
            // 'notification.sending' => [
            //     \App\Listeners\LogNotificationSending::class,
            // ],
            // 'notification.sent' => [
            //     \App\Listeners\LogNotificationSent::class,
            // ],
            // 'notification.failed' => [
            //     \App\Listeners\LogNotificationFailed::class,
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知渠道配置
    |--------------------------------------------------------------------------
    |
    | 这里配置通知渠道的相关设置。
    |
    */
    'channels' => [
        'mail' => [
            'driver' => 'mail',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'notifications',
        ],
        'broadcast' => [
            'driver' => 'broadcast',
            'connection' => env('BROADCAST_CONNECTION', 'redis'),
        ],
    ],
];
