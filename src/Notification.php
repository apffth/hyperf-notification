<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Messages\MailMessage;

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
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject($this->getSubject())
            ->line($this->getMessage());
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
     * 获取通知的主题。
     * @return string
     */
    protected function getSubject()
    {
        return '通知';
    }

    /**
     * 获取通知的消息内容。
     * @return string
     */
    protected function getMessage()
    {
        return '您有一条新通知';
    }
}
