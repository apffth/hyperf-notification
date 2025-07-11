<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\ChannelManager;
use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
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
 * @internal
 * @coversNothing
 */
class NotificationSenderTest extends TestCase
{
    public function testSendNow()
    {
        $user         = new User();
        $notification = new TestNotification();

        // Mock channels for 'mail' and 'database' as returned by TestNotification.via()
        $mailChannel = Mockery::mock(ChannelInterface::class);
        $mailChannel->shouldReceive('send')->once()->with($user, $notification)->andReturn(['success' => true]);

        $databaseChannel = Mockery::mock(ChannelInterface::class);
        $databaseChannel->shouldReceive('send')->once()->with($user, $notification)->andReturn(['success' => true]);

        $channelManager = Mockery::mock(ChannelManager::class);
        $channelManager->shouldReceive('get')->with('mail')->andReturn($mailChannel);
        $channelManager->shouldReceive('get')->with('database')->andReturn($databaseChannel);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatchSending')->times(2)->andReturn(true);
        $eventDispatcher->shouldReceive('dispatchSent')->times(2)->with(Mockery::on(function ($event) {
            return $event instanceof NotificationSent;
        }));
        $eventDispatcher->shouldReceive('dispatchFailed')->times(0); // Expect no failures

        $this->getContainer()->set(ChannelManager::class, $channelManager);
        $this->getContainer()->set(EventDispatcherInterface::class, $eventDispatcher);

        $sender = $this->getContainer()->get(NotificationSender::class);
        $sender->sendNow($user, $notification);

        $this->assertTrue(true);
    }

    public function testSendToQueue()
    {
        $user         = new User();
        $notification = (new TestNotification())->onQueue('test-queue');

        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('push')->once()->with(Mockery::type(NotificationJob::class), 0);

        $factory = Mockery::mock(DriverFactory::class);
        $factory->shouldReceive('get')->with('test-queue')->andReturn($driver);
        $this->getContainer()->set(DriverFactory::class, $factory);

        $sender = $this->getContainer()->get(NotificationSender::class);
        $sender->send($user, $notification);

        $this->assertTrue(true);
    }
}
