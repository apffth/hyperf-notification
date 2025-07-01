<?php

namespace Hyperf\Notification\Events;

use Hyperf\Notification\Notification;

class NotificationSending
{
    /**
     * 通知接收者
     */
    public $notifiable;

    /**
     * 通知实例
     */
    public Notification $notification;

    /**
     * 通知渠道
     */
    public string $channel;

    /**
     * 是否应该发送通知
     */
    public bool $shouldSend = true;

    /**
     * 创建新的事件实例
     */
    public function __construct($notifiable, Notification $notification, string $channel)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
        $this->channel = $channel;
    }

    /**
     * 阻止通知发送
     */
    public function preventSending(): void
    {
        $this->shouldSend = false;
    }

    /**
     * 检查是否应该发送通知
     */
    public function shouldSend(): bool
    {
        return $this->shouldSend;
    }

    /**
     * 获取通知接收者
     */
    public function getNotifiable()
    {
        return $this->notifiable;
    }

    /**
     * 获取通知实例
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }

    /**
     * 获取通知渠道
     */
    public function getChannel(): string
    {
        return $this->channel;
    }
}
