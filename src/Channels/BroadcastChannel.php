<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Channels;

use Apffth\Hyperf\Notification\Notification;

class BroadcastChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification): mixed
    {
        $data = $notification->toBroadcast($notifiable);

        // TODO: 实现广播通知逻辑
        // 这里可以集成 Redis、WebSocket 或其他广播系统

        // 返回广播结果信息
        return [
            'success' => true,
            'channel' => $this->getBroadcastChannel($notifiable),
            'event' => get_class($notification),
            'data' => $data,
            'broadcasted_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 获取广播频道名称
     * @param mixed $notifiable
     */
    protected function getBroadcastChannel($notifiable): string
    {
        if (method_exists($notifiable, 'receivesBroadcastNotificationsOn')) {
            return $notifiable->receivesBroadcastNotificationsOn($notifiable);
        }

        return 'App.Models.' . get_class($notifiable) . '.' . $notifiable->getKey();
    }
}
