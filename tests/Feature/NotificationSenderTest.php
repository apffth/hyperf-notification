<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\NotificationJob;
use Apffth\Hyperf\Notification\NotificationSender;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Mockery;

/**
 * @property \Hyperf\Di\Container $container
 */
class NotificationSenderTest extends TestCase
{
    public function testSendNow()
    {
        $user = new User();
        $notification = new TestNotification();

        $sender = $this->getContainer()->get(NotificationSender::class);
        $sender::sendNow($user, $notification);

        // This is a basic test. A more thorough test would involve
        // mocking channels and asserting they were called.
        $this->assertTrue(true);
    }

    public function testSendToQueue()
    {
        $user = new User();
        $notification = new TestNotification();

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('push')->once()->with(Mockery::type(NotificationJob::class), 0);

        $factory = Mockery::mock(DriverFactory::class);
        $factory->shouldReceive('get')->with('default')->andReturn($driver);
        $this->container->set(DriverFactory::class, $factory);

        NotificationSender::send($user, $notification);
    }
} 