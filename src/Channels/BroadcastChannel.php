<?php

namespace Hyperf\Notification\Channels;

use Hyperf\Notification\Notification;

class BroadcastChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        // TODO: 实现广播通知逻辑
    }
}
