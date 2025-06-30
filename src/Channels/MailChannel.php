<?php

namespace Hyperf\Notification\Channels;

use Hyperf\Notification\Notification;

class MailChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        // TODO: 实现邮件通知逻辑
    }
}
