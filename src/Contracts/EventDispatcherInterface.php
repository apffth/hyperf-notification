<?php

namespace Hyperf\Notification\Contracts;

use Hyperf\Notification\Events\NotificationSending;
use Hyperf\Notification\Events\NotificationSent;
use Hyperf\Notification\Events\NotificationFailed;

interface EventDispatcherInterface
{
    /**
     * 分发通知发送前事件
     */
    public function dispatchSending(NotificationSending $event): void;

    /**
     * 分发通知发送后事件
     */
    public function dispatchSent(NotificationSent $event): void;

    /**
     * 分发通知失败事件
     */
    public function dispatchFailed(NotificationFailed $event): void;

    /**
     * 添加事件监听器
     */
    public function listen(string $event, callable $listener): void;

    /**
     * 移除事件监听器
     */
    public function forget(string $event): void;
} 