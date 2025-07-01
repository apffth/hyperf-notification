<?php

namespace Examples\EventListeners;

use Hyperf\Notification\Events\NotificationFailed;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class LogNotificationFailed
{
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('notification');
    }

    public function handle(NotificationFailed $event): void
    {
        $this->logger->error('Notification failed event', [
            'notifiable' => $this->getNotifiableInfo($event->getNotifiable()),
            'notification' => get_class($event->getNotification()),
            'channel' => $event->getChannel(),
            'error' => $event->getErrorMessage(),
            'code' => $event->getErrorCode(),
            'failed_at' => $event->getFailedAt()->format('Y-m-d H:i:s'),
            'trace' => $event->getException()->getTraceAsString(),
        ]);

        // 示例：失败后执行其他操作
        $this->onNotificationFailed($event);
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

    protected function onNotificationFailed(NotificationFailed $event): void
    {
        // 示例：发送告警通知
        // $this->sendAlert($event);

        // 示例：重试发送
        // $this->retryNotification($event);

        // 示例：记录到失败队列
        // $this->logToFailureQueue($event);
    }

    protected function sendAlert(NotificationFailed $event): void
    {
        // 发送告警到管理员
        // 例如：发送邮件、短信、钉钉等
    }

    protected function retryNotification(NotificationFailed $event): void
    {
        // 重试发送通知
        // 注意：需要避免无限重试
    }

    protected function logToFailureQueue(NotificationFailed $event): void
    {
        // 记录到失败队列，供后续处理
    }
} 