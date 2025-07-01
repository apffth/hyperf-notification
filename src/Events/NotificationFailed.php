<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Events;

use Apffth\Hyperf\Notification\Notification;
use DateTimeImmutable;
use Throwable;

class NotificationFailed
{
    /**
     * 通知接收者.
     */
    public $notifiable;

    /**
     * 通知实例.
     */
    public Notification $notification;

    /**
     * 通知渠道.
     */
    public string $channel;

    /**
     * 异常信息.
     */
    public Throwable $exception;

    /**
     * 失败时间.
     */
    public DateTimeImmutable $failedAt;

    /**
     * 创建新的事件实例.
     * @param mixed $notifiable
     */
    public function __construct($notifiable, Notification $notification, string $channel, Throwable $exception)
    {
        $this->notifiable   = $notifiable;
        $this->notification = $notification;
        $this->channel      = $channel;
        $this->exception    = $exception;
        $this->failedAt     = new DateTimeImmutable();
    }

    /**
     * 获取通知接收者.
     */
    public function getNotifiable()
    {
        return $this->notifiable;
    }

    /**
     * 获取通知实例.
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }

    /**
     * 获取通知渠道.
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * 获取异常信息.
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * 获取失败时间.
     */
    public function getFailedAt(): DateTimeImmutable
    {
        return $this->failedAt;
    }

    /**
     * 获取错误消息.
     */
    public function getErrorMessage(): string
    {
        return $this->exception->getMessage();
    }

    /**
     * 获取错误代码
     */
    public function getErrorCode(): int
    {
        return $this->exception->getCode();
    }
}
