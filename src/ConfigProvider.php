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
                // 注册渠道管理器服务
                ChannelManager::class => ChannelManager::class,

                // 注册事件分发器服务
                EventDispatcherInterface::class => EventDispatcher::class,
                EventDispatcher::class          => EventDispatcher::class,

                // 注册 Twig 服务提供者
                TwigServiceProvider::class => TwigServiceProvider::class,
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
                    'id'          => 'notification',
                    'description' => 'The config for notification.',
                    'source'      => __DIR__ . '/../config/notification.php',
                    'destination' => BASE_PATH . '/config/autoload/notification.php',
                ],
                [
                    'id'          => 'twig',
                    'description' => 'The config for twig template engine.',
                    'source'      => __DIR__ . '/../config/twig.php',
                    'destination' => BASE_PATH . '/config/autoload/twig.php',
                ],
            ],
            'listeners' => [
                // 可以在这里注册事件监听器
                // 例如：
                // \Examples\EventListeners\LogNotificationSending::class,
                // \Examples\EventListeners\LogNotificationSent::class,
                // \Examples\EventListeners\LogNotificationFailed::class,
            ],
        ];
    }
}
