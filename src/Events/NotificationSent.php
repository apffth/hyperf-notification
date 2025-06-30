<?php

namespace Hyperf\Notification\Events;

use Hyperf\Notification\Notification;

class NotificationSent
{
    public $notifiable;
    public $notification;
    public $channel;
    public $response;

    public function __construct($notifiable, Notification $notification, $channel, $response = null)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
        $this->channel = $channel;
        $this->response = $response;
    }
}
