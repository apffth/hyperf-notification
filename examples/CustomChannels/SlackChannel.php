<?php

namespace Examples\CustomChannels;

use Hyperf\Notification\Channels\ChannelInterface;
use Hyperf\Notification\Notification;

/**
 * 示例：自定义 Slack 通知渠道
 *
 * 这个示例展示了如何创建自定义的 Slack 通知渠道
 */
class SlackChannel implements ChannelInterface
{
    /**
     * Slack 配置
     */
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'webhook_url' => '',
            'channel' => '#general',
            'username' => 'Notification Bot',
            'icon_emoji' => ':bell:',
        ], $config);
    }

    /**
     * 发送通知
     */
    public function send($notifiable, Notification $notification)
    {
        // 获取 Slack 消息内容
        $message = $notification->toSlack($notifiable);

        // 获取 Slack 频道
        $channel = $this->getSlackChannel($notifiable);

        // 发送到 Slack
        $this->sendToSlack($channel, $message);
    }

    /**
     * 获取 Slack 频道
     */
    protected function getSlackChannel($notifiable): string
    {
        // 如果 notifiable 有 routeNotificationFor 方法，使用它
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $channel = $notifiable->routeNotificationFor('slack');
            if ($channel) {
                return $channel;
            }
        }

        // 尝试从 notifiable 的属性中获取 Slack 频道
        if (property_exists($notifiable, 'slack_channel')) {
            return $notifiable->slack_channel;
        }

        // 返回默认频道
        return $this->config['channel'];
    }

    /**
     * 发送到 Slack
     */
    protected function sendToSlack(string $channel, array $message): void
    {
        $payload = [
            'channel' => $channel,
            'username' => $this->config['username'],
            'icon_emoji' => $this->config['icon_emoji'],
            'text' => $message['text'] ?? '',
            'attachments' => $message['attachments'] ?? [],
        ];

        // 模拟发送到 Slack
        echo "📢 发送到 Slack 频道: {$channel}\n";
        echo "👤 用户名: {$payload['username']}\n";
        echo "📝 内容: {$payload['text']}\n";
        echo "📎 附件数量: " . count($payload['attachments']) . "\n";
        echo "✅ Slack 消息发送成功！\n\n";

        // 实际项目中，这里应该调用 Slack Webhook API
        // 例如：
        // $this->callSlackWebhook($payload);
    }

    /**
     * 调用 Slack Webhook API（示例）
     */
    protected function callSlackWebhook(array $payload): void
    {
        // 这里实现具体的 Slack Webhook 调用
        // 例如：
        /*
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post($this->config['webhook_url'], [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Slack webhook failed: ' . $response->getBody());
            }
        } catch (\Exception $e) {
            throw new \Exception('Slack API error: ' . $e->getMessage());
        }
        */
    }
}
