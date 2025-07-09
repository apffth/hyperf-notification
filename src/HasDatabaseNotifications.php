<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Models\Notification as NotificationModel;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\MorphMany;

/**
 * Provides database-related notification methods for a Notifiable entity.
 * This trait should only be used on classes that extend Hyperf\DbConnection\Model\Model.
 */
trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(NotificationModel::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Get the entity's read notifications.
     */
    public function readNotifications(): MorphMany
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    /**
     * Mark all of the entity's notifications as read.
     */
    public function markNotificationsAsRead(): void
    {
        $this->unreadNotifications()->update(['read_at' => Carbon::now()]);
    }

    /**
     * Delete all of the entity's notifications.
     */
    public function deleteNotifications(): void
    {
        $this->notifications()->delete();
    }
}
