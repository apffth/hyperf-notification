<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\stubs;

use Apffth\Hyperf\Notification\Notification;
use RuntimeException;
use Throwable;

/**
 * 用于测试 beforeSend() / beforeSendOnce() 生命週期钩子的通知类。
 */
class BeforeSendTestNotification extends Notification
{
    public int $beforeSendCallCount = 0;

    /**
     * @var string[]
     */
    public array $callOrder = [];

    public bool $shouldThrow = false;

    public bool $failedCalled = false;

    public bool $shouldQueue = false;

    public function via($notifiable): array
    {
        $this->callOrder[] = 'via';

        return ['mail'];
    }

    public function shouldQueue($notifiable): bool
    {
        return $this->shouldQueue;
    }

    public function failed(Throwable $exception): void
    {
        $this->failedCalled = true;
    }

    protected function beforeSend(mixed $notifiable): void
    {
        $this->callOrder[] = 'beforeSend';
        ++$this->beforeSendCallCount;

        if ($this->shouldThrow) {
            throw new RuntimeException('beforeSend failed');
        }
    }
}
