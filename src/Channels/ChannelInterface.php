<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Channels;

use Apffth\Hyperf\Notification\Notification;

interface ChannelInterface
{
    /**
     * 发送通知。
     * @param mixed $notifiable
     */
    public function send($notifiable, Notification $notification);
}
