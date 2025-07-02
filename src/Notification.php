<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;

abstract class Notification
{
    use Queueable;

    /**
     * 获取通知的发送渠道。
     * @param mixed $notifiable
     * @return array|string
     */
    abstract public function via($notifiable);

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
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    /**
     * 获取通知的广播表示。
     * @param mixed $notifiable
     * @return array
     */
    public function toBroadcast($notifiable)
    {
        return $this->toArray($notifiable);
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
}
