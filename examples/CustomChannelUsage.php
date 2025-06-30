<?php

namespace Examples;

use Hyperf\Notification\Notification;
use Hyperf\Notification\NotificationSender;
use Examples\CustomChannels\SmsChannel;
use Examples\CustomChannels\SlackChannel;

/**
 * 自定义通知渠道使用示例
 *
 * 这个示例展示了如何在项目中使用自定义的通知渠道
 */

// 示例用户类
class User
{
    use \Hyperf\Notification\Notifiable;

    public $id;
    public $name;
    public $email;
    public $phone;
    public $slack_channel;

    public function __construct($id = 1, $name = '张三', $email = 'zhangsan@example.com', $phone = '13800138000', $slack_channel = '#general')
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->slack_channel = $slack_channel;
    }

    public function getKey()
    {
        return $this->id;
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function routeNotificationFor($driver)
    {
        switch ($driver) {
            case 'mail':
                return $this->email;
            case 'sms':
                return $this->phone;
            case 'slack':
                return $this->slack_channel;
            default:
                return $this;
        }
    }
}

// 支持多种渠道的通知类
class MultiChannelNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database', 'sms', 'slack'];
    }

    public function toMail($notifiable)
    {
        return (new \Hyperf\Notification\Messages\MailMessage())
            ->subject('多渠道通知测试')
            ->greeting('你好 ' . $notifiable->name)
            ->line('这是一个支持多种渠道的通知测试。')
            ->line('你将同时收到邮件、数据库通知、短信和 Slack 消息。');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => '多渠道通知测试',
            'message' => '这是一个支持多种渠道的通知测试。',
            'user_id' => $notifiable->id,
            'channels' => ['mail', 'database', 'sms', 'slack'],
        ];
    }

    public function toSms($notifiable)
    {
        return [
            'content' => "你好 {$notifiable->name}，这是一个短信通知测试。",
            'template' => 'SMS_123456789',
            'data' => [
                'name' => $notifiable->name,
                'time' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function toSlack($notifiable)
    {
        return [
            'text' => "你好 {$notifiable->name}，这是一个 Slack 通知测试！",
            'attachments' => [
                [
                    'title' => '通知详情',
                    'text' => '这是一个支持多种渠道的通知测试。',
                    'color' => 'good',
                    'fields' => [
                        [
                            'title' => '用户ID',
                            'value' => $notifiable->id,
                            'short' => true,
                        ],
                        [
                            'title' => '发送时间',
                            'value' => date('Y-m-d H:i:s'),
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}

// 使用示例
class CustomChannelUsageExample
{
    public static function run()
    {
        echo "=== 自定义通知渠道使用示例 ===\n\n";

        // 1. 注册自定义渠道
        echo "1. 注册自定义渠道...\n";

        // 注册短信渠道
        $smsChannel = new SmsChannel([
            'api_key' => 'your_sms_api_key',
            'api_secret' => 'your_sms_api_secret',
            'sign_name' => '您的应用',
            'template_code' => 'SMS_123456789',
        ]);
        NotificationSender::registerChannelInstance('sms', $smsChannel);

        // 注册 Slack 渠道
        $slackChannel = new SlackChannel([
            'webhook_url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
            'channel' => '#general',
            'username' => '通知机器人',
            'icon_emoji' => ':bell:',
        ]);
        NotificationSender::registerChannelInstance('slack', $slackChannel);

        echo "✅ 自定义渠道注册完成\n\n";

        // 2. 创建用户
        echo "2. 创建测试用户...\n";
        $user = new User(1, '张三', 'zhangsan@example.com', '13800138000', '#notifications');
        echo "✅ 用户创建完成: {$user->name}\n\n";

        // 3. 发送多渠道通知
        echo "3. 发送多渠道通知...\n";
        $notification = new MultiChannelNotification();
        $user->notify($notification);
        echo "✅ 多渠道通知发送完成\n\n";

        // 4. 显示注册的渠道
        echo "4. 当前注册的渠道:\n";
        $channels = ['mail', 'database', 'sms', 'slack'];
        foreach ($channels as $channel) {
            echo "   - {$channel}\n";
        }
        echo "\n";

        echo "=== 示例完成 ===\n";
        echo "请检查以下内容:\n";
        echo "1. 邮件是否发送到: {$user->email}\n";
        echo "2. 数据库 notifications 表是否有新记录\n";
        echo "3. 短信是否发送到: {$user->phone}\n";
        echo "4. Slack 消息是否发送到频道: {$user->slack_channel}\n";
    }
}

// 如果直接运行此文件，执行示例
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    CustomChannelUsageExample::run();
}
