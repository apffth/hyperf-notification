<?php

namespace Hyperf\Notification\Channels;

use Hyperf\Notification\Notification;
use Hyperf\Notification\Models\Notification as NotificationModel;
use Hyperf\Stringable\Str;

class DatabaseChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);

        // 获取 notifiable 的类型，优先使用别名
        $notifiableType = $this->getNotifiableType($notifiable);

        $notificationModel = new NotificationModel();
        $notificationModel->fill([
            'id' => (string) Str::uuid(),
            'type' => get_class($notification),
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiable->getKey(),
            'data' => $data,
        ]);

        $notificationModel->save();
    }

    /**
     * 获取 notifiable 的类型，优先使用别名
     */
    protected function getNotifiableType($notifiable): string
    {
        // 如果 notifiable 实现了 getMorphClass 方法，优先使用
        // if (method_exists($notifiable, 'getMorphClass')) {
        //     return $notifiable->getMorphClass();
        // }

        // 如果 notifiable 有 morphClass 属性，使用该属性
        if (property_exists($notifiable, 'morphClass')) {
            return $notifiable->morphClass ?: get_class($notifiable);
        }

        // 如果 notifiable 有 MORPH_CLASS 常量，使用该常量
        // $reflection = new \ReflectionClass($notifiable);
        // if ($reflection->hasConstant('MORPH_CLASS')) {
        //     return $reflection->getConstant('MORPH_CLASS');
        // }

        // 默认使用类名
        return get_class($notifiable);
    }
}
