<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Contracts;

use Apffth\Hyperf\Notification\Events\NotificationFailed;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Psr\Log\LoggerInterface;

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

    /**
     * 设置是否启用事件.
     */
    public function setEnabled(bool $enabled): void;

    /**
     * 检查是否启用事件.
     */
    public function isEnabled(): bool;

    /**
     * 获取日志实例
     */
    public function getLogger(): ?LoggerInterface;

    /**
     * 设置日志实例
     */
    public function setLogger(?LoggerInterface $logger): void;
}
