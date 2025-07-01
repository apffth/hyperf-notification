<?php

namespace Hyperf\Notification;

use Hyperf\Notification\Contracts\ShouldQueue;
use Hyperf\AsyncQueue\Job;
use Hyperf\Notification\Events\NotificationSending;
use Hyperf\Notification\Events\NotificationSent;
use Hyperf\Stringable\Str;

use function Hyperf\AsyncQueue\dispatch;

class NotificationSender
{
    /**
     * 渠道管理器实例
     */
    protected static ?ChannelManager $channelManager = null;

    /**
     * 获取渠道管理器
     */
    protected static function getChannelManager(): ChannelManager
    {
        if (static::$channelManager === null) {
            static::$channelManager = new ChannelManager();
        }
        return static::$channelManager;
    }

    /**
     * 设置渠道管理器
     */
    public static function setChannelManager(ChannelManager $manager): void
    {
        static::$channelManager = $manager;
    }

    /**
     * 注册自定义渠道
     */
    public static function registerChannel(string $name, string $channelClass): void
    {
        static::getChannelManager()->register($name, $channelClass);
    }

    /**
     * 注册自定义渠道实例
     */
    public static function registerChannelInstance(string $name, $channel): void
    {
        static::getChannelManager()->registerInstance($name, $channel);
    }

    /**
     * 发送通知到指定 notifiable。
     * 参考 Laravel 11 的 send 方法实现
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public static function send($notifiable, Notification $notification)
    {
        // 检查是否应该队列化
        if ($notification instanceof ShouldQueue && $notification->shouldQueue($notifiable)) {
            static::queueNotification($notifiable, $notification);
            return;
        }

        // 检查是否应该发送
        if ($notification instanceof ShouldQueue && !$notification->shouldSend($notifiable)) {
            return;
        }

        static::sendNow($notifiable, $notification);
    }

    /**
     * 立即发送通知
     * 参考 Laravel 11 的 sendNow 方法
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public static function sendNow($notifiable, Notification $notification)
    {
        $channels = $notification->via($notifiable);

        foreach ($channels as $channel) {
            // 触发发送前事件
            $sendingEvent = new NotificationSending($notifiable, $notification, $channel);
            // 这里可以添加事件分发逻辑

            // 检查是否应该发送（通过事件监听器）
            $shouldSend = true;
            // 这里可以添加事件监听器逻辑来检查是否应该发送

            if (!$shouldSend) {
                continue;
            }

            try {
                $channelInstance = static::getChannelManager()->get($channel);
                $channelInstance->send($notifiable, $notification);

                // 触发发送后事件
                $sentEvent = new NotificationSent($notifiable, $notification, $channel);
                // 这里可以添加事件分发逻辑
            } catch (\Throwable $e) {
                // 处理发送失败
                if ($notification instanceof ShouldQueue) {
                    $notification->failed($e);
                }
                throw $e;
            }
        }
    }

    /**
     * 将通知推送到队列
     * 参考 Laravel 11 的队列化实现
     */
    protected static function queueNotification($notifiable, Notification $notification): void
    {
        // 创建队列任务
        $job = new class ($notifiable, $notification) extends Job {
            private string $uniqid;
            protected mixed $notifiable;
            protected Notification $notification;

            public function __construct(mixed $notifiable, Notification $notification)
            {
                $this->uniqid = Str::uuid()->toString();
                $this->notifiable = $notifiable;
                $this->notification = $notification;
            }

            public function handle(): void
            {
                try {
                    // 检查是否应该发送
                    if ($this->notification instanceof ShouldQueue && !$this->notification->shouldSend($this->notifiable)) {
                        return;
                    }

                    NotificationSender::sendNow($this->notifiable, $this->notification);
                } catch (\Throwable $e) {
                    // 处理失败
                    if ($this->notification instanceof ShouldQueue) {
                        $this->notification->failed($e);
                    }
                    throw $e;
                }
            }
        };

        // 推送到队列
        dispatch($job, $notification->getDelay(), $notification->getTries(), $notification->getQueueName());
    }
}
