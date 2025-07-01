<?php

namespace Hyperf\Notification;

use Hyperf\Notification\Channels\MailChannel;
use Hyperf\Notification\Channels\DatabaseChannel;
use Hyperf\Notification\Channels\BroadcastChannel;
use Hyperf\Notification\Contracts\ShouldQueue;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Hyperf\AsyncQueue\Driver\DriverFactory;

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
     * 将通知推送到队列
     */
    protected static function queueNotification($notifiable, Notification $notification): void
    {
        $container = ApplicationContext::getContainer();
        $driverFactory = $container->get(DriverFactory::class);

        // 获取队列名称
        $queueName = $notification->queue ?? 'default';
        $driver = $driverFactory->get($queueName);

        // 创建队列任务
        $job = new class ($notifiable, $notification) extends Job {
            protected $notifiable;
            protected $notification;

            public function __construct($notifiable, $notification)
            {
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
        $driver->push($job);
    }

    /**
     * 立即发送通知
     */
    public static function sendNow($notifiable, Notification $notification)
    {
        $channels = (array) $notification->via($notifiable);
        $channelManager = static::getChannelManager();

        foreach ($channels as $channel) {
            // 首先尝试从渠道管理器获取
            $channelInstance = $channelManager->get($channel);

            if ($channelInstance !== null) {
                $channelInstance->send($notifiable, $notification);
                continue;
            }

            // 如果渠道管理器中没有，尝试使用旧的方式
            $method = 'sendVia' . ucfirst($channel);
            if (method_exists(static::class, $method)) {
                static::{$method}($notifiable, $notification);
            } else {
                // 尝试自动加载渠道类
                $channelClass = __NAMESPACE__ . '\\Channels\\' . ucfirst($channel) . 'Channel';
                if (class_exists($channelClass)) {
                    (new $channelClass())->send($notifiable, $notification);
                } else {
                    throw new \InvalidArgumentException("Channel '{$channel}' not found.");
                }
            }
        }
    }
}
