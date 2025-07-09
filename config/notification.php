<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the default queue connection, delay, and retry
    | attempts for all notifications that are queued by the application.
    |
    */
    'queue' => [
        'queue' => env('NOTIFICATION_QUEUE', 'notification'),
        'delay' => (int) env('NOTIFICATION_QUEUE_DELAY', 0),
        'tries' => (int) env('NOTIFICATION_QUEUE_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Events Configuration
    |--------------------------------------------------------------------------
    |
    | This option controls whether the notification events are dispatched by
    | the notification component.
    |
    */
    'events' => [
        'enabled' => env('NOTIFICATION_EVENTS_ENABLED', true),
    ],
];
