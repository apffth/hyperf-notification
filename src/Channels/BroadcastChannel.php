<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Channels;

use Apffth\Hyperf\Notification\Notification;

class BroadcastChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        // TODO: 实现广播通知逻辑
    }
}
