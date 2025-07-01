<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
use Apffth\Hyperf\Notification\Events\NotificationFailed;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Stringable\Str;
use Throwable;

use function Hyperf\AsyncQueue\dispatch;

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
     * @param mixed $channel
     */
    public static function registerChannelInstance(string $name, $channel): void
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
        $channels        = $notification->via($notifiable);
        $eventDispatcher = static::getEventDispatcher();

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
                $response        = $channelInstance->send($notifiable, $notification);

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
            $config       = $container->get('config');
            $eventsConfig = $config->get('notification.events', []);

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
        // 创建队列任务
        $job = new class($notifiable, $notification) extends Job {
            private string $uniqid;

            protected mixed $notifiable;

            protected Notification $notification;

            public function __construct(mixed $notifiable, Notification $notification)
            {
                $this->uniqid       = Str::uuid()->toString();
                $this->notifiable   = $notifiable;
                $this->notification = $notification;
            }

            public function handle(): void
            {
                try {
                    // 检查是否应该发送
                    if (! $this->notification->shouldSend($this->notifiable)) {
                        return;
                    }

                    NotificationSender::sendNow($this->notifiable, $this->notification);
                } catch (Throwable $e) {
                    // 处理失败
                    $this->notification->failed($e);
                    throw $e;
                }
            }
        };

        // 推送到队列
        // dispatch($job, $notification->getDelay() ?? 0, $notification->getTries() ?? 3, $notification->getQueueName() ?? 'notification');

        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get('hyperf', 'default');
        $logger->info('queueNotification', [
            'notifiable' => $notifiable->id,
            'delay'      => $notification->getDelay(),
            'tries'      => $notification->getTries(),
            'queue'      => $notification->getQueueName(),
            'driver'     => ApplicationContext::getContainer()->get(DriverFactory::class),
            'queue'      => ApplicationContext::getContainer()
                ->get(DriverFactory::class)
                ->get($notification->getQueueName() ?? 'notification'),
        ]);

        if (is_int($notification->getTries())) {
            $job->setMaxAttempts($notification->getTries());
        }

        ApplicationContext::getContainer()
            ->get(DriverFactory::class)
            ->get($notification->getQueueName() ?? 'notification')
            ->push($job, $notification->getDelay() ?? 0);
    }
}
