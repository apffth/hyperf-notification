<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Hyperf\Stringable\Str;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

abstract class Notification
{
    use Queueable;

    /**
     * 通知 ID.
     */
    protected string $id = '';

    /**
     * 渠道返回值存储.
     */
    protected array $channelResponses = [];

    /**
     * 获取通知应该发送的渠道。
     * @param mixed $notifiable
     */
    abstract public function via($notifiable): array;

    /**
     * 获取通知 ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 设置通知 ID.
     */
    public function setId(): void
    {
        $this->id = (string) Str::uuid()->toString();
    }

    /**
     * 获取通知的数组表示。
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [];
    }

    /**
     * 获取通知的邮件表示。
     * @param mixed $notifiable
     */
    public function toMail($notifiable): TemplatedEmail
    {
        return new TemplatedEmail();
    }

    /**
     * 获取通知的数据库表示。
     * @param mixed $notifiable
     */
    public function toDatabase($notifiable): array
    {
        return [];
    }

    /**
     * 获取通知的广播表示。
     * @param mixed $notifiable
     */
    public function toBroadcast($notifiable): array
    {
        return [];
    }

    /**
     * 获取通知的短信表示。
     * @param mixed $notifiable
     * @return mixed
     */
    public function toSms($notifiable)
    {
        return null;
    }

    /**
     * 获取通知的 Line Message 表示。
     * @param mixed $notifiable
     * @return mixed
     */
    public function toLineMessage($notifiable)
    {
        return null;
    }

    /**
     * 获取通知的 Line Notify 表示。
     * @param mixed $notifiable
     * @return mixed
     */
    public function toLineNotify($notifiable)
    {
        return null;
    }

    /**
     * 获取通知的 Slack 表示。
     * @param mixed $notifiable
     * @return mixed
     */
    public function toSlack($notifiable)
    {
        return null;
    }

    /**
     * 获取通知的 App Push 表示。
     * @param mixed $notifiable
     * @return mixed
     */
    public function toAppPush($notifiable)
    {
        return null;
    }

    /**
     * 发送后处理.
     * @param mixed $notifiable
     */
    public function afterSend($notifiable): void {}

    /**
     * 设置渠道返回值
     */
    public function setChannelResponses(array $responses): void
    {
        $this->channelResponses = $responses;
    }

    /**
     * 获取所有渠道的返回值
     */
    public function getChannelResponses(): array
    {
        return $this->channelResponses;
    }

    /**
     * 获取指定渠道的返回值
     */
    public function getChannelResponse(string $channel): mixed
    {
        return $this->channelResponses[$channel] ?? null;
    }

    /**
     * 检查是否有指定渠道的返回值
     */
    public function hasChannelResponse(string $channel): bool
    {
        return isset($this->channelResponses[$channel]);
    }

    /**
     * 获取第一个渠道的返回值
     */
    public function getFirstChannelResponse(): mixed
    {
        return reset($this->channelResponses) ?: null;
    }
}
