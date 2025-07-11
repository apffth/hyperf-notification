<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
use Apffth\Hyperf\Notification\Events\NotificationFailed;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

class EventDispatcher implements EventDispatcherInterface
{
    /**
     * 事件监听器.
     */
    protected array $listeners = [];

    /**
     * 容器实例.
     */
    protected ContainerInterface $container;

    /**
     * 日志实例.
     */
    protected ?LoggerInterface $logger = null;

    /**
     * 是否启用事件.
     */
    protected bool $enabled;

    public function __construct(ContainerInterface $container, bool $enabled = true)
    {
        $this->container = $container;
        $this->enabled   = $enabled;

        if ($enabled) {
            $this->initializeLogger();
        }
    }

    /**
     * 分发通知发送前事件.
     */
    public function dispatchSending(NotificationSending $event): bool
    {
        if (! $this->enabled) {
            return true;
        }

        $this->dispatch(NotificationSending::class, $event);

        // Log the event
        if ($this->logger) {
            try {
                $notification = $event->getNotification();
                $logData      = [
                    'notifiable'   => $this->getNotifiableInfo($event->getNotifiable()),
                    'notification' => get_class($notification),
                    'channel'      => $event->getChannel(),
                    'properties'   => $this->getProperties($notification),
                ];
                $this->logger->info('Notification sending', $logData);
            } catch (Throwable $e) {
                error_log('Failed to log notification sending event: ' . $e->getMessage());
            }
        }

        return $event->shouldSend();
    }

    /**
     * 分发通知发送后事件.
     */
    public function dispatchSent(NotificationSent $event): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->dispatch('notification.sent', $event);

        // 记录日志
        if ($this->logger) {
            try {
                $notification = $event->getNotification();

                $this->logger->info('Notification sent', [
                    'notifiable'   => $this->getNotifiableInfo($event->getNotifiable()),
                    'notification' => get_class($notification),
                    'channel'      => $event->getChannel(),
                    'properties'   => $this->getProperties($notification),
                    'successful'   => $event->wasSuccessful(),
                    'sent_at'      => $event->getSentAt()->format('Y-m-d H:i:s'),
                ]);
            } catch (Throwable $e) {
                // 日志记录失败不应影响事件分发
                error_log('Failed to log notification sent event: ' . $e->getMessage());
            }
        }
    }

    /**
     * 分发通知失败事件.
     */
    public function dispatchFailed(NotificationFailed $event): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->dispatch('notification.failed', $event);

        // 记录日志
        if ($this->logger) {
            try {
                $notification = $event->getNotification();

                $this->logger->error('Notification failed', [
                    'notifiable'   => $this->getNotifiableInfo($event->getNotifiable()),
                    'notification' => get_class($notification),
                    'channel'      => $event->getChannel(),
                    'properties'   => $this->getProperties($notification),
                    'error'        => $event->getErrorMessage(),
                    'code'         => $event->getErrorCode(),
                    'failed_at'    => $event->getFailedAt()->format('Y-m-d H:i:s'),
                ]);
            } catch (Throwable $e) {
                // 日志记录失败不应影响事件分发
                error_log('Failed to log notification failed event: ' . $e->getMessage());
            }
        }
    }

    /**
     * 添加事件监听器.
     */
    public function listen(string $event, callable $listener): void
    {
        if (! isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;
    }

    /**
     * 移除事件监听器.
     */
    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }

    /**
     * 设置是否启用事件.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;

        // 如果启用事件但日志未初始化，尝试初始化日志
        if ($enabled && $this->logger === null) {
            $this->initializeLogger();
        }
    }

    /**
     * 检查是否启用事件.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 获取日志实例.
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * 设置日志实例.
     */
    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * 初始化日志实例.
     */
    protected function initializeLogger(): void
    {
        try {
            // 检查容器中是否有 LoggerFactory
            if ($this->container->has(LoggerFactory::class)) {
                $loggerFactory = $this->container->get(LoggerFactory::class);
                $this->logger  = $loggerFactory->get('notification');
            }
        } catch (Throwable $e) {
            // 如果获取 LoggerFactory 失败，记录错误但不影响事件系统运行
            // 这里可以选择记录到 error_log 或者静默处理
            error_log('Failed to initialize notification logger: ' . $e->getMessage());
        }
    }

    /**
     * 分发事件.
     * @param mixed $payload
     */
    protected function dispatch(string $event, $payload): void
    {
        if (! isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            if (! $payload->shouldSend()) {
                break;
            }

            try {
                if (is_callable($listener)) {
                    $response = call_user_func($listener, $payload);
                    if ($response === false) {
                        $payload->preventSending();
                    }
                }
            } catch (Throwable $e) {
                if ($this->logger) {
                    try {
                        $this->logger->error('Event listener error', [
                            'event' => $event,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    } catch (Throwable $logError) {
                        // 如果日志记录也失败，使用 error_log 作为后备
                        error_log('Event listener error (logger failed): ' . $e->getMessage());
                        error_log('Logger error: ' . $logError->getMessage());
                    }
                } else {
                    // 如果没有日志实例，使用 error_log 作为后备
                    error_log('Event listener error: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * 获取通知接收者信息.
     * @param mixed $notifiable
     */
    protected function getNotifiableInfo($notifiable): array
    {
        if (is_object($notifiable)) {
            return [
                'type' => get_class($notifiable),
                'id'   => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
            ];
        }

        return [
            'type'  => gettype($notifiable),
            'value' => is_scalar($notifiable) ? $notifiable : null,
        ];
    }

    /**
     * 获取通知类属性.
     */
    protected function getProperties(Notification $notification): array
    {
        try {
            $reflection = new ReflectionClass($notification);

            $className = $reflection->getName();

            $props = [];
            foreach ($reflection->getProperties() as $prop) {
                if ($prop->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                $prop->setAccessible(true);

                $value = $prop->getValue($notification);
                if (is_object($value)) {
                    continue;
                }

                $props[$prop->getName()] = $this->sanitizeValue($value);
            }

            return $props;
        } catch (Throwable $e) {
            // 如果获取属性失败，返回空数组
            return [];
        }
    }

    /**
     * 清理敏感数据.
     * @param mixed $value
     */
    protected function sanitizeValue($value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                // 为了保持一致性，不记录数组中的对象
                if (is_object($item)) {
                    continue;
                }
                $result[$key] = $this->sanitizeValue($item); // 递归调用
            }

            return $result;
        }

        if (is_string($value)) {
            // 检查是否包含敏感信息
            $sensitivePatterns = [
                '/password/i',
                '/token/i',
                '/secret/i',
                '/key/i',
                '/api_key/i',
                '/private_key/i',
            ];

            foreach ($sensitivePatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return '[SENSITIVE_DATA]';
                }
            }
        }

        return $value;
    }
}
