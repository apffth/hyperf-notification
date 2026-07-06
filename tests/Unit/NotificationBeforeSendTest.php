<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Unit;

use Apffth\Hyperf\Notification\ChannelManager;
use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
use Apffth\Hyperf\Notification\NotificationJob;
use Apffth\Hyperf\Notification\NotificationSender;
use Apffth\Hyperf\Notification\Tests\stubs\BeforeSendTestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Mockery;
use RuntimeException;

/**
 * @internal
 * @coversNothing
 */
class NotificationBeforeSendTest extends TestCase
{
    /**
     * 1. 仅执行一次：透过 sendNow() 呼叫一次后计数器为 1，
     *    再手动呼叫 beforeSendOnce() 第二次，计数器仍为 1（幂等）。
     */
    public function testBeforeSendOnlyExecutesOnce()
    {
        $user         = new User();
        $notification = new BeforeSendTestNotification();

        $mailChannel = Mockery::mock(ChannelInterface::class);
        $mailChannel->shouldReceive('send')->once()->andReturn(['success' => true]);

        $channelManager = Mockery::mock(ChannelManager::class);
        $channelManager->shouldReceive('get')->with('mail')->andReturn($mailChannel);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatchSending')->andReturn(true);
        $eventDispatcher->shouldReceive('dispatchSent');
        $eventDispatcher->shouldReceive('dispatchFailed');

        $this->getContainer()->set(ChannelManager::class, $channelManager);
        $this->getContainer()->set(EventDispatcherInterface::class, $eventDispatcher);

        /** @var NotificationSender $sender */
        $sender = $this->getContainer()->get(NotificationSender::class);
        $sender->sendNow($user, $notification);

        $this->assertSame(1, $notification->beforeSendCallCount);

        // 手动再次呼叫，计数器应保持不变（幂等）。
        $notification->beforeSendOnce($user);

        $this->assertSame(1, $notification->beforeSendCallCount);
    }

    /**
     * 2. 执行顺序：beforeSend() 于 via() 之前被呼叫。
     */
    public function testBeforeSendIsCalledBeforeVia()
    {
        $user         = new User();
        $notification = new BeforeSendTestNotification();

        $mailChannel = Mockery::mock(ChannelInterface::class);
        $mailChannel->shouldReceive('send')->once()->andReturnUsing(function ($notifiable, $notif) {
            $notif->callOrder[] = 'toXxx';

            return ['success' => true];
        });

        $channelManager = Mockery::mock(ChannelManager::class);
        $channelManager->shouldReceive('get')->with('mail')->andReturn($mailChannel);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatchSending')->andReturn(true);
        $eventDispatcher->shouldReceive('dispatchSent');
        $eventDispatcher->shouldReceive('dispatchFailed');

        $this->getContainer()->set(ChannelManager::class, $channelManager);
        $this->getContainer()->set(EventDispatcherInterface::class, $eventDispatcher);

        /** @var NotificationSender $sender */
        $sender = $this->getContainer()->get(NotificationSender::class);
        $sender->sendNow($user, $notification);

        $this->assertSame(['beforeSend', 'via', 'toXxx'], $notification->callOrder);
    }

    /**
     * 3. 向后兼容：未覆写 beforeSend() 的既有通知类完整发送流程应保持不变。
     */
    public function testBackwardCompatibilityForNotificationWithoutBeforeSend()
    {
        $user         = new User();
        $notification = new TestNotification();

        $mailChannel = Mockery::mock(ChannelInterface::class);
        $mailChannel->shouldReceive('send')->once()->andReturn(['success' => true]);

        $databaseChannel = Mockery::mock(ChannelInterface::class);
        $databaseChannel->shouldReceive('send')->once()->andReturn(['success' => true]);

        $channelManager = Mockery::mock(ChannelManager::class);
        $channelManager->shouldReceive('get')->with('mail')->andReturn($mailChannel);
        $channelManager->shouldReceive('get')->with('database')->andReturn($databaseChannel);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatchSending')->andReturn(true);
        $eventDispatcher->shouldReceive('dispatchSent');
        $eventDispatcher->shouldReceive('dispatchFailed');

        $this->getContainer()->set(ChannelManager::class, $channelManager);
        $this->getContainer()->set(EventDispatcherInterface::class, $eventDispatcher);

        /** @var NotificationSender $sender */
        $sender = $this->getContainer()->get(NotificationSender::class);
        $sender->sendNow($user, $notification);

        $this->assertTrue(true);
    }

    /**
     * 4. 队列路径下的例外与重试语义：
     *    - NotificationJob::handle() 会捕获例外并呼叫 Notification::failed()；
     *    - 例外会被重新抛出；
     *    - 新建的 Job 实例（模拟重试）中 prepared 状态为初始值 false。
     */
    public function testBeforeSendExceptionInQueuePathIsHandledAndRetryable()
    {
        $user                      = new User();
        $notification              = new BeforeSendTestNotification();
        $notification->shouldThrow = true;

        $channelManager  = Mockery::mock(ChannelManager::class);
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatchSending')->never();

        $this->getContainer()->set(ChannelManager::class, $channelManager);
        $this->getContainer()->set(EventDispatcherInterface::class, $eventDispatcher);

        $job = new NotificationJob($user, $notification);

        try {
            $job->handle();
            $this->fail('Expected exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('beforeSend failed', $e->getMessage());
        }

        $this->assertTrue($notification->failedCalled);
        $this->assertSame(1, $notification->beforeSendCallCount);

        // prepared 未被错误标记为已完成：再次呼叫 beforeSendOnce() 仍会执行 beforeSend()。
        $notification->shouldThrow = false;
        $notification->beforeSendOnce($user);
        $this->assertSame(2, $notification->beforeSendCallCount);

        // 模拟重试：全新的 Job 实例（新的 Notification 物件），prepared 预设为 false。
        $retryNotification = new BeforeSendTestNotification();
        $retryNotification->beforeSendOnce($user);
        $this->assertSame(1, $retryNotification->beforeSendCallCount);
    }

    /**
     * 5. 同步路径下的例外传播：shouldQueue() 为 false 且 beforeSend() 抛出例外时，
     *    例外应直接从 NotificationSender::send() 冒泡给呼叫方。
     */
    public function testBeforeSendExceptionInSyncPathPropagatesToCaller()
    {
        $user                      = new User();
        $notification              = new BeforeSendTestNotification();
        $notification->shouldQueue = false;
        $notification->shouldThrow = true;

        $channelManager  = Mockery::mock(ChannelManager::class);
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatchSending')->never();

        $this->getContainer()->set(ChannelManager::class, $channelManager);
        $this->getContainer()->set(EventDispatcherInterface::class, $eventDispatcher);

        /** @var NotificationSender $sender */
        $sender = $this->getContainer()->get(NotificationSender::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('beforeSend failed');

        $sender->send($user, $notification);
    }
}
