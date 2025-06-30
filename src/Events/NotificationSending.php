<?php

namespace Hyperf\Notification\Events;

use Hyperf\Notification\Notification;

class NotificationSending
{
    public $notifiable;
    public $notification;
    public $channel;

    public function __construct($notifiable, Notification $notification, $channel)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
        $this->channel = $channel;
    }
}
