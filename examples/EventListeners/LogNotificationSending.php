<?php

namespace Examples\EventListeners;

use Hyperf\Notification\Events\NotificationSending;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class LogNotificationSending
{
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('notification');
    }

    public function handle(NotificationSending $event): void
    {
        $this->logger->info('Notification sending event', [
            'notifiable' => $this->getNotifiableInfo($event->getNotifiable()),
            'notification' => get_class($event->getNotification()),
            'channel' => $event->getChannel(),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        // 示例：根据条件阻止发送
        // if ($event->getChannel() === 'mail' && $this->isMaintenanceMode()) {
        //     $event->preventSending();
        // }
    }

    protected function getNotifiableInfo($notifiable): array
    {
        if (is_object($notifiable)) {
            return [
                'type' => get_class($notifiable),
                'id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
            ];
        }

        return [
            'type' => gettype($notifiable),
            'value' => is_scalar($notifiable) ? $notifiable : null,
        ];
    }

    protected function isMaintenanceMode(): bool
    {
        // 检查是否处于维护模式
        return false;
    }
} 