<?php

namespace Hyperf\Notification\Channels;

use Hyperf\Notification\Notification;
use Hyperf\Notification\Models\Notification as NotificationModel;
use Hyperf\Stringable\Str;
use Hyperf\Utils\ApplicationContext;

class DatabaseChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);

        $notificationModel = new NotificationModel();
        $notificationModel->fill([
            'id' => (string) Str::uuid(),
            'type' => get_class($notification),
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'data' => $data,
        ]);

        $notificationModel->save();
    }
}
