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
];
