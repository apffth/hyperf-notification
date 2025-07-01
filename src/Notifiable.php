<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Models\Notification as NotificationModel;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\MorphMany;

trait Notifiable
{
    /**
     * 发送通知。
     */
    public function notify(Notification $notification)
    {
        return NotificationSender::send($this, $notification);
    }

    /**
     * 获取通知路由。
     * @param string $channel
     * @return mixed
     */
    public function routeNotificationFor($channel)
    {
        $method = 'routeNotificationFor' . ucfirst($channel);
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
        return null;
    }

    /**
     * 获取实体的所有通知。
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(NotificationModel::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    /**
     * 获取实体的未读通知。
     */
    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * 获取实体的已读通知。
     */
    public function readNotifications(): MorphMany
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    /**
     * 标记所有通知为已读。
     */
    public function markNotificationsAsRead(): void
    {
        $this->unreadNotifications()->update(['read_at' => Carbon::now()]);
    }

    /**
     * 删除所有通知。
     */
    public function deleteNotifications(): void
    {
        $this->notifications()->delete();
    }
}
