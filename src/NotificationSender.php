<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
use Apffth\Hyperf\Notification\Events\NotificationFailed;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Apffth\Hyperf\Notification\Exceptions\NotificationException;
use Hyperf\Context\ApplicationContext;
use Throwable;

use function Hyperf\AsyncQueue\dispatch;
use function Hyperf\Config\config;

class NotificationSender
{
    /**
     * 渠道管理器实例.
     */
    protected static ?ChannelManager $channelManager = null;

    /**
     * 事件分发器实例.
     */
    protected static ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * 设置渠道管理器.
     */
    public static function setChannelManager(ChannelManager $manager): void
    {
        static::$channelManager = $manager;
    }

    /**
     * 设置事件分发器.
     */
    public static function setEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        static::$eventDispatcher = $dispatcher;
    }

    /**
     * 注册自定义渠道.
     */
    public static function registerChannel(string $name, string $channelClass): void
    {
        static::getChannelManager()->register($name, $channelClass);
    }

    /**
     * 注册自定义渠道实例.
     */
    public static function registerChannelInstance(string $name, ChannelInterface $channel): void
    {
        static::getChannelManager()->registerInstance($name, $channel);
    }

    /**
     * 添加事件监听器.
     */
    public static function listen(string $event, callable $listener): void
    {
        static::getEventDispatcher()->listen($event, $listener);
    }

    /**
     * 发送通知到指定 notifiable。
     * 参考 Laravel 11 的 send 方法实现.
     * @param mixed $notifiable
     */
    public static function send($notifiable, Notification $notification)
    {
        // 检查是否应该队列化
        if ($notification->shouldQueue($notifiable)) {
            static::queueNotification($notifiable, $notification);
            return;
        }

        // 检查是否应该发送
        if (! $notification->shouldSend($notifiable)) {
            return;
        }

        static::sendNow($notifiable, $notification);
    }

    /**
     * 立即发送通知
     * 参考 Laravel 11 的 sendNow 方法.
     * @param mixed $notifiable
     */
    public static function sendNow($notifiable, Notification $notification)
    {
        $notification->setId();

        $channels        = $notification->via($notifiable);
        $eventDispatcher = static::getEventDispatcher();
        $responses       = [];

        foreach ($channels as $channel) {
            // 触发发送前事件
            $sendingEvent = new NotificationSending($notifiable, $notification, $channel);
            $eventDispatcher->dispatchSending($sendingEvent);

            // 检查是否应该发送（通过事件监听器）
            if (! $sendingEvent->shouldSend()) {
                continue;
            }

            try {
                $channelInstance = static::getChannelManager()->get($channel);
                
                if (! $channelInstance instanceof ChannelInterface) {
                    throw new NotificationException(500, 'Channel instance not found');
                }

                $response = $channelInstance->send($notifiable, $notification);

                // 收集渠道返回值
                $responses[$channel] = $response;

                // 触发发送后事件
                $sentEvent = new NotificationSent($notifiable, $notification, $channel, $response);
                $eventDispatcher->dispatchSent($sentEvent);
            } catch (Throwable $e) {
                // 触发失败事件
                $failedEvent = new NotificationFailed($notifiable, $notification, $channel, $e);
                $eventDispatcher->dispatchFailed($failedEvent);

                // 处理发送失败
                $notification->failed($e);
                throw $e;
            }
        }

        // 将渠道返回值传递给通知类
        if (! empty($responses)) {
            $notification->setChannelResponses($responses);
        }

        // 调用通知类的 afterSend 方法
        if (method_exists($notification, 'afterSend')) {
            $notification->afterSend($notifiable);
        }
    }

    /**
     * 获取渠道管理器.
     */
    protected static function getChannelManager(): ChannelManager
    {
        if (static::$channelManager === null) {
            static::$channelManager = new ChannelManager();
        }
        return static::$channelManager;
    }

    /**
     * 获取事件分发器.
     */
    protected static function getEventDispatcher(): EventDispatcherInterface
    {
        if (static::$eventDispatcher === null) {
            $container    = ApplicationContext::getContainer();
            $eventsConfig = config('notification.events', []);

            static::$eventDispatcher = new EventDispatcher(
                $container,
                $eventsConfig['enabled'] ?? true
            );
        }
        return static::$eventDispatcher;
    }

    /**
     * 将通知推送到队列
     * 参考 Laravel 11 的队列化实现.
     * @param mixed $notifiable
     */
    protected static function queueNotification($notifiable, Notification $notification): void
    {
        $job = new NotificationJob($notifiable, $notification);

        dispatch(
            $job,
            $notification->getDelay()     ?? 0,
            $notification->getTries()     ?? 1,
            $notification->getQueueName() ?? 'notification'
        );
    }
}
