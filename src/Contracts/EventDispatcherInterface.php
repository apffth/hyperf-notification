<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Contracts;

use Apffth\Hyperf\Notification\Events\NotificationFailed;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;

interface EventDispatcherInterface
{
    /**
     * 分发通知发送前事件.
     */
    public function dispatchSending(NotificationSending $event): void;

    /**
     * 分发通知发送后事件.
     */
    public function dispatchSent(NotificationSent $event): void;

    /**
     * 分发通知失败事件.
     */
    public function dispatchFailed(NotificationFailed $event): void;

    /**
     * 添加事件监听器.
     */
    public function listen(string $event, callable $listener): void;

    /**
     * 移除事件监听器.
     */
    public function forget(string $event): void;
}
