<?php

namespace Examples\EventListeners;

use Hyperf\Notification\Events\NotificationSent;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class LogNotificationSent
{
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('notification');
    }

    public function handle(NotificationSent $event): void
    {
        $this->logger->info('Notification sent event', [
            'notifiable' => $this->getNotifiableInfo($event->getNotifiable()),
            'notification' => get_class($event->getNotification()),
            'channel' => $event->getChannel(),
            'successful' => $event->wasSuccessful(),
            'sent_at' => $event->getSentAt()->format('Y-m-d H:i:s'),
            'response' => $this->formatResponse($event->getResponse()),
        ]);

        // 示例：发送成功后执行其他操作
        if ($event->wasSuccessful()) {
            $this->onNotificationSent($event);
        }
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

    protected function formatResponse($response): mixed
    {
        if (is_object($response)) {
            return get_class($response);
        }

        if (is_array($response)) {
            return 'array(' . count($response) . ')';
        }

        return $response;
    }

    protected function onNotificationSent(NotificationSent $event): void
    {
        // 示例：更新用户通知统计
        // $notifiable = $event->getNotifiable();
        // if (method_exists($notifiable, 'incrementNotificationCount')) {
        //     $notifiable->incrementNotificationCount();
        // }

        // 示例：发送到外部系统
        // $this->sendToExternalSystem($event);
    }

    protected function sendToExternalSystem(NotificationSent $event): void
    {
        // 发送到外部系统的逻辑
        // 例如：发送到 Slack、钉钉等
    }
} 