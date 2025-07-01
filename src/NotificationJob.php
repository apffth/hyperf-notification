<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Hyperf\AsyncQueue\Job;
use Hyperf\Stringable\Str;
use Throwable;

class NotificationJob extends Job
{
    private string $uniqid;

    public function __construct(protected mixed $notifiable, protected Notification $notification)
    {
        $this->uniqid = Str::uuid()->toString();
    }

    public function handle()
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
}
