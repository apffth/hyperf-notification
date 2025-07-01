<?php

namespace Apffth\Hyperf\Notification;

use function Hyperf\Config\config;

/**
 * 队列化通知的 Trait
 */
trait Queueable
{
    /**
     * 队列名称
     */
    protected ?string $queue = null;

    /**
     * 延迟时间（秒）
     */
    protected ?int $delay = null;

    /**
     * 最大重试次数
     */
    protected ?int $tries = null;

    /**
     * 获取队列名称
     */
    public function getQueueName(): ?string
    {
        return $this->queue ?? config('notification.queue.queue');
    }

    /**
     * 设置队列名称
     * 参考 Laravel 11: onQueue($queue)
     */
    public function onQueue(string $queue): static
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * 获取延迟时间
     */
    public function getDelay(): ?int
    {
        return $this->delay ?? config('notification.queue.delay');
    }

    /**
     * 设置延迟时间
     * 参考 Laravel 11: delay($delay)
     */
    public function delay(int $delay): static
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * 获取最大重试次数
     */
    public function getTries(): ?int
    {
        return $this->tries ?? config('notification.queue.tries');
    }

    /**
     * 设置最大重试次数
     * 参考 Laravel 11: tries($tries)
     */
    public function tries(int $tries): static
    {
        $this->tries = $tries;
        return $this;
    }

    /**
     * 处理失败的方法
     * 参考 Laravel 11: failed($exception)
     */
    public function failed(\Throwable $exception): void
    {
        // 默认实现，子类可以重写
        if (method_exists($this, 'onFailure')) {
            $this->onFailure($exception);
        }
    }

    /**
     * 判断是否应该队列化
     * 参考 Laravel 11: shouldQueue($notifiable)
     */
    public function shouldQueue($notifiable): bool
    {
        return true;
    }

    /**
     * 判断是否应该发送
     * 参考 Laravel 11: shouldSend($notifiable)
     */
    public function shouldSend($notifiable): bool
    {
        return true;
    }
}
