<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Hyperf\Context\ApplicationContext;

/**
 * Provides methods for sending notifications and routing them.
 */
trait RoutesNotifications
{
    /**
     * Send the given notification.
     *
     * @param  \Apffth\Hyperf\Notification\Notification  $notification
     * @return void
     */
    public function notify($notification)
    {
        ApplicationContext::getContainer()->get(NotificationSender::class)->send($this, $notification);
    }

    /**
     * Get the notification routing information for the given channel.
     *
     * @param  string  $channel
     * @param  \Apffth\Hyperf\Notification\Notification|null  $notification
     * @return mixed
     */
    public function routeNotificationFor(string $channel, $notification = null)
    {
        if (method_exists($this, $method = 'routeNotificationFor'.ucfirst($channel))) {
            return $this->{$method}($notification);
        }

        switch ($channel) {
            case 'mail':
                return $this->email ?? null;
        }

        return null;
    }
} 