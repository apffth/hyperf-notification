<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\ChannelManager;
use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
use Apffth\Hyperf\Notification\EventDispatcher;
use Apffth\Hyperf\Notification\Events\NotificationSent;
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
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSendNow()
    {
        $user         = new User();
        $notification = new TestNotification();

        $channel = Mockery::mock(ChannelInterface::class);
        $channel->shouldReceive('send')->once()->with($user, $notification);

        $channelManager = Mockery::mock(ChannelManager::class);
        $channelManager->shouldReceive('get')->with('test_channel')->andReturn($channel);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatchSending')->once();
        $eventDispatcher->shouldReceive('dispatchSent')->once()->with(Mockery::on(function ($event) {
            return $event instanceof NotificationSent;
        }));

        $sender = new NotificationSender($channelManager, $eventDispatcher);
        $sender->sendNow($user, $notification);
    }

    public function testSendToQueue()
    {
        $user         = new User();
        $notification = (new TestNotification())->onQueue('test-queue');

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('push')->once()->with(Mockery::type(NotificationJob::class), 0);

        $factory = Mockery::mock(DriverFactory::class);
        $factory->shouldReceive('get')->with('test-queue')->andReturn($driver);
        $this->container->set(DriverFactory::class, $factory);

        $sender = $this->container->get(NotificationSender::class);
        $sender->send($user, $notification);
    }
} 