<?php

namespace Hyperf\Notification;

use Hyperf\Notification\Contracts\EventDispatcherInterface;
use Hyperf\Notification\Events\NotificationSending;
use Hyperf\Notification\Events\NotificationSent;
use Hyperf\Notification\Events\NotificationFailed;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class EventDispatcher implements EventDispatcherInterface
{
    /**
     * 事件监听器
     */
    protected array $listeners = [];

    /**
     * 容器实例
     */
    protected ContainerInterface $container;

    /**
     * 日志实例
     */
    protected $logger;

    /**
     * 是否启用事件
     */
    protected bool $enabled;

    public function __construct(ContainerInterface $container, bool $enabled = true)
    {
        $this->container = $container;
        $this->enabled = $enabled;
        
        if ($enabled) {
            $this->logger = $container->get(LoggerFactory::class)->get('notification');
        }
    }

    /**
     * 分发通知发送前事件
     */
    public function dispatchSending(NotificationSending $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->dispatch('notification.sending', $event);
        
        // 记录日志
        if ($this->logger) {
            $this->logger->info('Notification sending', [
                'notifiable' => $this->getNotifiableInfo($event->getNotifiable()),
                'notification' => get_class($event->getNotification()),
                'channel' => $event->getChannel(),
            ]);
        }
    }

    /**
     * 分发通知发送后事件
     */
    public function dispatchSent(NotificationSent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->dispatch('notification.sent', $event);
        
        // 记录日志
        if ($this->logger) {
            $this->logger->info('Notification sent', [
                'notifiable' => $this->getNotifiableInfo($event->getNotifiable()),
                'notification' => get_class($event->getNotification()),
                'channel' => $event->getChannel(),
                'successful' => $event->wasSuccessful(),
                'sent_at' => $event->getSentAt()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * 分发通知失败事件
     */
    public function dispatchFailed(NotificationFailed $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->dispatch('notification.failed', $event);
        
        // 记录日志
        if ($this->logger) {
            $this->logger->error('Notification failed', [
                'notifiable' => $this->getNotifiableInfo($event->getNotifiable()),
                'notification' => get_class($event->getNotification()),
                'channel' => $event->getChannel(),
                'error' => $event->getErrorMessage(),
                'code' => $event->getErrorCode(),
                'failed_at' => $event->getFailedAt()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * 添加事件监听器
     */
    public function listen(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        $this->listeners[$event][] = $listener;
    }

    /**
     * 移除事件监听器
     */
    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }

    /**
     * 分发事件
     */
    protected function dispatch(string $event, $payload): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            try {
                if (is_callable($listener)) {
                    call_user_func($listener, $payload);
                }
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->error('Event listener error', [
                        'event' => $event,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }
    }

    /**
     * 获取通知接收者信息
     */
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

    /**
     * 设置是否启用事件
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * 检查是否启用事件
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
} 