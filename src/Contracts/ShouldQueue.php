<?php

namespace Hyperf\Notification\Contracts;

/**
 * 队列化通知接口
 * 参考 Laravel 11 的 ShouldQueue 接口实现
 */
interface ShouldQueue
{
    /**
     * 处理失败的方法
     * 当通知发送失败时调用
     */
    public function failed(\Throwable $exception): void;

    /**
     * 判断是否应该队列化
     * 返回 true 表示应该队列化，false 表示立即发送
     */
    public function shouldQueue($notifiable): bool;

    /**
     * 判断是否应该发送
     * 返回 true 表示应该发送，false 表示不发送
     */
    public function shouldSend($notifiable): bool;
}
