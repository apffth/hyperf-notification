<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Unit;

use Apffth\Hyperf\Notification\EventDispatcher;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Mockery;

/**
 * @internal
 * @coversNothing
 */
class EventDispatcherTest extends TestCase
{
    public function testEventDispatching()
    {
        $dispatcher   = $this->getContainer()->get(EventDispatcher::class);
        $notifiable   = new User();
        $notification = new TestNotification();

        $listener = Mockery::mock();
        $listener->shouldReceive('handle')->once();

        $dispatcher->listen(NotificationSending::class, [$listener, 'handle']);
        $dispatcher->dispatchSending(new NotificationSending($notifiable, $notification, 'mail'));

        $this->assertTrue(true); // Add an assertion to avoid risky test warning
    }

    public function testPreventSending()
    {
        $dispatcher = $this->getContainer()->get(EventDispatcher::class);
        $event      = new NotificationSending(new User(), new TestNotification(), 'mail');

        $dispatcher->listen(NotificationSending::class, function (NotificationSending $event) {
            return false;
        });

        $this->assertFalse($dispatcher->dispatchSending($event));
    }
}
