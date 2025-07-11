<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__, 2));

        return [
            'dependencies' => [
                EventDispatcherInterface::class => EventDispatcher::class,
            ],
            'publish' => [
                [
                    'id'          => 'config',
                    'description' => 'The config for notification.',
                    'source'      => __DIR__ . '/../config/notification.php',
                    'destination' => BASE_PATH . '/config/autoload/notification.php',
                ],
                [
                    'id'          => 'config-mail',
                    'description' => 'The config for mail.',
                    'source'      => __DIR__ . '/../config/mail.php',
                    'destination' => BASE_PATH . '/config/autoload/mail.php',
                ],
                [
                    'id'          => 'config-twig',
                    'description' => 'The config for twig.',
                    'source'      => __DIR__ . '/../config/twig.php',
                    'destination' => BASE_PATH . '/config/autoload/twig.php',
                ],
                [
                    'id'          => 'migration',
                    'description' => 'The migration for notifications.',
                    'source'      => __DIR__ . '/../database/migrations/2024_01_01_000000_create_notifications_table.php',
                    'destination' => BASE_PATH . '/migrations/2024_01_01_000000_create_notifications_table.php',
                ],
            ],
        ];
    }
}
