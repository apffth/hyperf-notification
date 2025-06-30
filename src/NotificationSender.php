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
     * 发送通知到指定 notifiable。
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public static function send($notifiable, Notification $notification)
    {
        if ($notification instanceof ShouldQueue) {
            // 推送到队列
            $container = ApplicationContext::getContainer();
            $driverFactory = $container->get(DriverFactory::class);
            $driver = $driverFactory->get('default');

            $job = new class ($notifiable, $notification) extends Job {
                protected $notifiable;
                protected $notification;
                public function __construct($notifiable, $notification)
                {
                    $this->notifiable = $notifiable;
                    $this->notification = $notification;
                }
                public function handle()
                {
                    NotificationSender::sendNow($this->notifiable, $this->notification);
                }
            };
            $driver->push($job);
            return;
        }
        static::sendNow($notifiable, $notification);
    }

    public static function sendNow($notifiable, Notification $notification)
    {
        $channels = (array) $notification->via($notifiable);
        foreach ($channels as $channel) {
            $method = 'sendVia' . ucfirst($channel);
            if (method_exists(static::class, $method)) {
                static::{$method}($notifiable, $notification);
            } else {
                $channelClass = __NAMESPACE__ . '\\Channels\\' . ucfirst($channel) . 'Channel';
                if (class_exists($channelClass)) {
                    (new $channelClass())->send($notifiable, $notification);
                }
            }
        }
    }
}
