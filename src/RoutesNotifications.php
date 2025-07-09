<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

/**
 * Provides the core `notify` and `routeNotificationFor` methods.
 * This trait can be used on any class to make it "routes notifications".
 */
trait RoutesNotifications
{
    /**
     * Send the given notification.
     */
    public function notify(Notification $notification): void
    {
        NotificationSender::send($this, $notification);
    }

    /**
     * Get the notification routing information for the given channel.
     *
     * @param string $driver
     * @param null|Notification $notification
     * @return mixed
     */
    public function routeNotificationFor($driver, $notification = null)
    {
        if (method_exists($this, $method = 'routeNotificationFor' . ucfirst($driver))) {
            return $this->{$method}($notification);
        }

        return null;
    }
}
