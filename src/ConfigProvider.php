<?php

declare(strict_types=1);

namespace Hyperf\Notification;

class ConfigProvider
{
    public function __invoke(): array
    {
        defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__, 2));

        return [
            'dependencies' => [
                // 可以在这里注册依赖注入
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'notification',
                    'description' => 'The config for notification.',
                    'source' => __DIR__ . '/../config/notification.php',
                    'destination' => BASE_PATH . '/config/autoload/notification.php',
                ],
            ],
        ];
    }
}
