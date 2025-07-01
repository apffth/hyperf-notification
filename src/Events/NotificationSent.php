<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Events;

use Apffth\Hyperf\Notification\Notification;
use DateTimeImmutable;

class NotificationSent
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
     * 渠道响应结果.
     */
    public $response;

    /**
     * 发送时间.
     */
    public DateTimeImmutable $sentAt;

    /**
     * 创建新的事件实例.
     * @param mixed $notifiable
     * @param null|mixed $response
     */
    public function __construct($notifiable, Notification $notification, string $channel, $response = null)
    {
        $this->notifiable   = $notifiable;
        $this->notification = $notification;
        $this->channel      = $channel;
        $this->response     = $response;
        $this->sentAt       = new DateTimeImmutable();
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
     * 获取渠道响应结果.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * 获取发送时间.
     */
    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    /**
     * 检查是否发送成功
     */
    public function wasSuccessful(): bool
    {
        return $this->response !== false && $this->response !== null;
    }
}
