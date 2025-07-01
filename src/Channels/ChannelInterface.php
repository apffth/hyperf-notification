<?php

namespace Hyperf\Notification\Channels;

use Hyperf\Notification\Notification;

interface ChannelInterface
{
    /**
     * 发送通知。
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification);
}
