<?php

namespace Hyperf\Notification\Channels;

use Hyperf\Notification\Notification;
use Hyperf\Notification\Models\Notification as NotificationModel;
use Hyperf\Stringable\Str;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;

class DatabaseChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        try {
            $data = $notification->toDatabase($notifiable);

            // 记录调试信息
            if (ApplicationContext::hasContainer()) {
                $container = ApplicationContext::getContainer();
                if ($container->has(StdoutLoggerInterface::class)) {
                    $logger = $container->get(StdoutLoggerInterface::class);
                    $logger->info('DatabaseChannel: 开始存储通知', [
                        'notifiable_type' => get_class($notifiable),
                        'notifiable_id' => $notifiable->getKey(),
                        'notification_type' => get_class($notification),
                        'data' => $data
                    ]);
                }
            }

            $notificationModel = new NotificationModel();
            $notificationModel->fill([
                'id' => (string) Str::uuid(),
                'type' => get_class($notification),
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->getKey(),
                'data' => $data,
            ]);

            $result = $notificationModel->save();

            // 记录结果
            if (ApplicationContext::hasContainer()) {
                $container = ApplicationContext::getContainer();
                if ($container->has(StdoutLoggerInterface::class)) {
                    $logger = $container->get(StdoutLoggerInterface::class);
                    if ($result) {
                        $logger->info('DatabaseChannel: 通知存储成功', [
                            'notification_id' => $notificationModel->id
                        ]);
                    } else {
                        $logger->error('DatabaseChannel: 通知存储失败');
                    }
                }
            }

        } catch (\Exception $e) {
            // 记录错误
            if (ApplicationContext::hasContainer()) {
                $container = ApplicationContext::getContainer();
                if ($container->has(StdoutLoggerInterface::class)) {
                    $logger = $container->get(StdoutLoggerInterface::class);
                    $logger->error('DatabaseChannel: 存储通知时发生错误', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            throw $e;
        }
    }
}
