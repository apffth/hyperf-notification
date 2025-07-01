<?php

namespace Examples;

use Hyperf\Notification\NotificationSender;
use Hyperf\Notification\Events\NotificationSending;
use Hyperf\Notification\Events\NotificationSent;
use Hyperf\Notification\Events\NotificationFailed;
use Examples\EventListeners\LogNotificationSending;
use Examples\EventListeners\LogNotificationSent;
use Examples\EventListeners\LogNotificationFailed;

/**
 * 事件使用示例
 */
class EventUsageExample
{
    public function setupEventListeners(): void
    {
        // 注册事件监听器
        NotificationSender::listen('notification.sending', function (NotificationSending $event) {
            echo "通知发送前事件: {$event->getChannel()}\n";
            
            // 示例：根据条件阻止发送
            if ($event->getChannel() === 'mail' && $this->isMaintenanceMode()) {
                $event->preventSending();
                echo "维护模式，阻止邮件发送\n";
            }
        });

        NotificationSender::listen('notification.sent', function (NotificationSent $event) {
            echo "通知发送后事件: {$event->getChannel()}\n";
            echo "发送成功: " . ($event->wasSuccessful() ? '是' : '否') . "\n";
            echo "发送时间: " . $event->getSentAt()->format('Y-m-d H:i:s') . "\n";
        });

        NotificationSender::listen('notification.failed', function (NotificationFailed $event) {
            echo "通知失败事件: {$event->getChannel()}\n";
            echo "错误信息: " . $event->getErrorMessage() . "\n";
            echo "错误代码: " . $event->getErrorCode() . "\n";
            echo "失败时间: " . $event->getFailedAt()->format('Y-m-d H:i:s') . "\n";
        });
    }

    public function setupClassBasedListeners(): void
    {
        // 使用基于类的事件监听器
        $container = \Hyperf\Context\ApplicationContext::getContainer();
        
        NotificationSender::listen('notification.sending', function (NotificationSending $event) use ($container) {
            $listener = new LogNotificationSending($container);
            $listener->handle($event);
        });

        NotificationSender::listen('notification.sent', function (NotificationSent $event) use ($container) {
            $listener = new LogNotificationSent($container);
            $listener->handle($event);
        });

        NotificationSender::listen('notification.failed', function (NotificationFailed $event) use ($container) {
            $listener = new LogNotificationFailed($container);
            $listener->handle($event);
        });
    }

    public function conditionalSendingExample(): void
    {
        // 条件发送示例
        NotificationSender::listen('notification.sending', function (NotificationSending $event) {
            $notifiable = $event->getNotifiable();
            $notification = $event->getNotification();
            
            // 示例：只在工作时间发送邮件
            if ($event->getChannel() === 'mail' && !$this->isWorkingHours()) {
                $event->preventSending();
                echo "非工作时间，阻止邮件发送\n";
            }
            
            // 示例：检查用户是否允许接收通知
            if (method_exists($notifiable, 'shouldReceiveNotifications') && !$notifiable->shouldReceiveNotifications()) {
                $event->preventSending();
                echo "用户不允许接收通知\n";
            }
            
            // 示例：检查通知频率限制
            if ($this->isRateLimited($notifiable, $notification)) {
                $event->preventSending();
                echo "通知频率限制，阻止发送\n";
            }
        });
    }

    public function postSendingActionsExample(): void
    {
        // 发送后操作示例
        NotificationSender::listen('notification.sent', function (NotificationSent $event) {
            if ($event->wasSuccessful()) {
                // 更新用户通知统计
                $notifiable = $event->getNotifiable();
                if (method_exists($notifiable, 'incrementNotificationCount')) {
                    $notifiable->incrementNotificationCount();
                }
                
                // 发送到外部系统
                $this->sendToExternalSystem($event);
                
                // 记录通知历史
                $this->logNotificationHistory($event);
            }
        });
    }

    public function failureHandlingExample(): void
    {
        // 失败处理示例
        NotificationSender::listen('notification.failed', function (NotificationFailed $event) {
            // 记录失败日志
            $this->logFailure($event);
            
            // 发送告警
            $this->sendAlert($event);
            
            // 重试逻辑
            $this->handleRetry($event);
        });
    }

    protected function isMaintenanceMode(): bool
    {
        // 检查是否处于维护模式
        return false;
    }

    protected function isWorkingHours(): bool
    {
        $hour = (int) date('H');
        return $hour >= 9 && $hour <= 18;
    }

    protected function isRateLimited($notifiable, $notification): bool
    {
        // 检查通知频率限制
        // 这里可以实现具体的频率限制逻辑
        return false;
    }

    protected function sendToExternalSystem($event): void
    {
        // 发送到外部系统的逻辑
        echo "发送到外部系统\n";
    }

    protected function logNotificationHistory($event): void
    {
        // 记录通知历史
        echo "记录通知历史\n";
    }

    protected function logFailure($event): void
    {
        // 记录失败日志
        echo "记录失败日志\n";
    }

    protected function sendAlert($event): void
    {
        // 发送告警
        echo "发送告警\n";
    }

    protected function handleRetry($event): void
    {
        // 处理重试逻辑
        echo "处理重试逻辑\n";
    }
} 