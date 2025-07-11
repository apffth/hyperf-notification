<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Throwable;

use function Hyperf\Config\config;

/**
 * 队列化通知的 Trait.
 */
trait Queueable
{
    /**
     * 队列名称.
     */
    protected ?string $queue = null;

    /**
     * 延迟时间（秒）.
     */
    protected ?int $delay = null;

    /**
     * 最大重试次数.
     */
    protected ?int $tries = null;

    /**
     * 获取队列名称.
     */
    public function getQueueName(): ?string
    {
        return $this->queue ?? config('notification.queue.queue');
    }

    /**
     * 设置队列名称
     * 参考 Laravel 11: onQueue($queue).
     */
    public function onQueue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * 获取延迟时间.
     */
    public function getDelay(): ?int
    {
        return $this->delay ?? config('notification.queue.delay');
    }

    /**
     * 设置延迟时间
     * 参考 Laravel 11: delay($delay).
     */
    public function delay(int $delay): static
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * 获取最大重试次数.
     */
    public function getTries(): ?int
    {
        return $this->tries ?? config('notification.queue.tries');
    }

    /**
     * 设置最大重试次数
     * 参考 Laravel 11: tries($tries).
     */
    public function tries(int $tries): static
    {
        $this->tries = $tries;

        return $this;
    }

    /**
     * 处理失败的方法
     * 当通知发送失败时（无论是同步还是通过队列），此方法都将被调用。
     * 用户可以在他们的 Notification 类中实现 `handleFailure` 方法来处理失败逻辑。
     * 参考 Laravel 11: failed($exception).
     */
    public function failed(Throwable $exception): void
    {
        // 默认实现，子类可以重写此方法，
        // 或定义 handleFailure 方法来添加自定义的失败处理逻辑。
        if (method_exists($this, 'handleFailure')) {
            $this->handleFailure($exception);
        }
    }

    /**
     * 判断是否应该队列化
     * 参考 Laravel 11: shouldQueue($notifiable).
     * @param mixed $notifiable
     */
    public function shouldQueue($notifiable): bool
    {
        return true;
    }

    /**
     * 判断是否应该发送
     * 参考 Laravel 11: shouldSend($notifiable).
     * @param mixed $notifiable
     */
    public function shouldSend($notifiable): bool
    {
        return true;
    }
}
