<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

use function Hyperf\Config\config;

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

                // 注册邮件服务
                // MailerInterface::class => function () {
                //     $defaultMailer = config('mail.default_mailer');
                //     $mailer        = config('mail.mailers.' . $defaultMailer);

                //     $transport = new EsmtpTransport(
                //         host: $mailer['host'],
                //         port: $mailer['port'],
                //         tls: $mailer['encryption'] === 'tls'
                //     );

                //     if (! empty($mailer['username'])) {
                //         $transport->setUsername($mailer['username']);
                //     }

                //     if (! empty($mailer['password'])) {
                //         $transport->setPassword($mailer['password']);
                //     }

                //     return new Mailer($transport);
                // },
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
