<?php

declare(strict_types=1);

namespace Examples;

use Apffth\Hyperf\Notification\Notification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class WelcomeNotification extends Notification
{
    public function __construct(
        protected string $userName,
        protected string $welcomeMessage = '欢迎加入我们！'
    ) {
    }

    /**
     * 获取通知应该发送的渠道
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * 获取通知的邮件表示
     */
    public function toMail($notifiable): TemplatedEmail
    {
        $email = new TemplatedEmail();
        $email->subject('欢迎 ' . $this->userName)
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'userName' => $this->userName,
                'message' => $this->welcomeMessage,
            ]);

        return $email;
    }

    /**
     * 获取通知的数据库表示
     */
    public function toDatabase($notifiable): array
    {
        return [
            'message' => $this->welcomeMessage,
            'user_name' => $this->userName,
            'type' => 'welcome',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 通知发送完成后的回调方法
     * 可以在这里处理渠道返回值
     */
    public function afterSend(mixed $response, string $channel, mixed $notifiable): void
    {        
        // 处理邮件渠道的返回值
        if ($channel == 'mail') {
            echo "邮件发送成功！\n";
            echo "收件人: {$response['to']}\n";
            echo "主题: {$response['subject']}\n";
            echo "发送时间: {$response['sent_at']}\n";
        }

        // 处理数据库渠道的返回值
        if ($channel == 'database') {
            echo "数据库通知创建成功！\n";
            echo "通知ID: {$response['notification_id']}\n";
            echo "通知类型: {$response['type']}\n";
            echo "创建时间: {$response['created_at']}\n";
        }

        // 记录所有渠道的发送结果
        $this->logChannelResults($response, $channel);
    }

    /**
     * 记录渠道发送结果
     */
    protected function logChannelResults(mixed $response, string $channel): void
    {
        $logData = [
            'notification_class' => static::class,
            'user_name' => $this->userName,
            'channel' => $channel,
            'channel_results' => $response,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // 这里可以将结果记录到日志文件或数据库
        echo "=== 渠道发送结果日志 ===\n";
        echo json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo "========================\n";
    }

    /**
     * 获取第一个成功的渠道响应
     */
    public function getFirstSuccessfulResponse(): ?array
    {
        foreach ($this->channelResponses as $channel => $response) {
            if (isset($response['success']) && $response['success']) {
                return [
                    'channel' => $channel,
                    'response' => $response,
                ];
            }
        }

        return null;
    }

    /**
     * 检查是否所有渠道都发送成功
     */
    public function allChannelsSuccessful(): bool
    {
        if (empty($this->channelResponses)) {
            return false;
        }

        foreach ($this->channelResponses as $response) {
            if (!isset($response['success']) || !$response['success']) {
                return false;
            }
        }

        return true;
    }
} 