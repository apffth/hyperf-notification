<?php

namespace Hyperf\Notification;

use Hyperf\Notification\Contracts\ShouldQueue;

/**
 * 队列化通知的 Trait
 * 参考 Laravel 的 Queueable trait 实现
 */
trait Queueable
{
    /**
     * 队列名称
     */
    public ?string $queue = null;

    /**
     * 延迟时间（秒）
     */
    public ?int $delay = null;

    /**
     * 最大重试次数
     */
    public ?int $tries = null;

    /**
     * 超时时间（秒）
     */
    public ?int $timeout = null;

    /**
     * 重试间隔（秒）
     */
    public ?int $retryAfter = null;

    /**
     * 获取队列名称
     */
    public function getQueueName(): ?string
    {
        return $this->queue;
    }

    /**
     * 设置队列名称
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
        return $this->delay;
    }

    /**
     * 设置延迟时间
     */
    public function setDelay(int $delay): static
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * 获取最大重试次数
     */
    public function getTries(): ?int
    {
        return $this->tries;
    }

    /**
     * 设置最大重试次数
     */
    public function setTries(int $tries): static
    {
        $this->tries = $tries;
        return $this;
    }

    /**
     * 获取超时时间
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * 设置超时时间
     */
    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 获取重试间隔
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * 设置重试间隔
     */
    public function setRetryAfter(int $retryAfter): static
    {
        $this->retryAfter = $retryAfter;
        return $this;
    }

    /**
     * 处理失败的方法
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
     */
    public function shouldQueue($notifiable): bool
    {
        return true;
    }

    /**
     * 判断是否应该发送
     */
    public function shouldSend($notifiable): bool
    {
        return true;
    }
}
