<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

/**
 * Trait for notifiable entities.
 */
trait Notifiable
{
    use HasDatabaseNotifications;
    use RoutesNotifications;
}
