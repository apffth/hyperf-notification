<?php

namespace Hyperf\Notification\Contracts;

interface ShouldQueue
{
    /**
     * 处理失败的方法
     */
    public function failed(\Throwable $exception): void;

    /**
     * 判断是否应该队列化
     */
    public function shouldQueue($notifiable): bool;

    /**
     * 判断是否应该发送
     */
    public function shouldSend($notifiable): bool;
}
