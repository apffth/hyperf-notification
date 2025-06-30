<?php

namespace Hyperf\Notification\Channels;

interface ChannelInterface
{
    /**
     * 发送通知。
     * @param mixed $notifiable
     * @param \Hyperf\Notification\Notification $notification
     * @return void
     */
    public function send($notifiable, \Hyperf\Notification\Notification $notification);
}
