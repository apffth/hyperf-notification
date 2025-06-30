<?php

namespace Hyperf\Notification\Tests;

use Hyperf\Notification\Notification;
use Hyperf\Notification\Messages\MailMessage;
use Hyperf\Notification\Contracts\ShouldQueue;

// 测试通知类
class TestNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('测试通知')
            ->greeting('你好！')
            ->line('这是一个测试通知。')
            ->line('发送时间：' . date('Y-m-d H:i:s'))
            ->action('查看详情', 'https://example.com')
            ->line('感谢您的使用！');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => '测试通知',
            'message' => '这是一个测试通知，发送时间：' . date('Y-m-d H:i:s'),
            'type' => 'test',
            'data' => [
                'user_id' => $notifiable->id ?? 1,
                'timestamp' => time(),
            ],
        ];
    }
}

// 队列化测试通知
class QueueableTestNotification extends TestNotification implements ShouldQueue
{
    public function via($notifiable)
    {
        return ['database'];
    }
}

// 模拟可通知实体
class TestNotifiable
{
    public $id;
    public $name;
    public $email;

    public function __construct($id = 1, $name = '测试用户', $email = 'test@example.com')
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }

    public function getKey()
    {
        return $this->id;
    }

    public function routeNotificationFor($channel)
    {
        if ($channel === 'mail') {
            return $this->email;
        }
        return null;
    }

    // 模拟 notify 方法
    public function notify($notification)
    {
        echo "📧 发送通知到: {$this->name} ({$this->email})\n";
        echo "📋 通知类型: " . get_class($notification) . "\n";

        $channels = $notification->via($this);
        foreach ($channels as $channel) {
            echo "📡 通过渠道发送: {$channel}\n";

            switch ($channel) {
                case 'mail':
                    $mailData = $notification->toMail($this);
                    echo "📮 邮件内容: {$mailData->subject}\n";
                    break;
                case 'database':
                    $dbData = $notification->toDatabase($this);
                    echo "💾 数据库内容: " . json_encode($dbData, JSON_UNESCAPED_UNICODE) . "\n";
                    break;
            }
        }

        if ($notification instanceof ShouldQueue) {
            echo "📬 通知已推送到队列\n";
        } else {
            echo "✅ 通知发送完成\n";
        }
    }
}

// 测试运行器
class NotificationTestRunner
{
    public static function run()
    {
        echo "🚀 开始测试通知功能...\n\n";

        // 测试普通通知
        self::testNormalNotification();

        // 测试队列化通知
        self::testQueueableNotification();

        echo "\n✅ 所有测试完成！\n";
    }

    private static function testNormalNotification()
    {
        echo "📧 测试普通通知...\n";

        $notifiable = new TestNotifiable();
        $notification = new TestNotification();

        try {
            $notifiable->notify($notification);
            echo "✅ 普通通知测试成功！\n\n";
        } catch (\Exception $e) {
            echo "❌ 普通通知测试失败: " . $e->getMessage() . "\n\n";
        }
    }

    private static function testQueueableNotification()
    {
        echo "📬 测试队列化通知...\n";

        $notifiable = new TestNotifiable();
        $notification = new QueueableTestNotification();

        try {
            $notifiable->notify($notification);
            echo "✅ 队列化通知测试成功！\n\n";
        } catch (\Exception $e) {
            echo "❌ 队列化通知测试失败: " . $e->getMessage() . "\n\n";
        }
    }
}

// 如果直接运行此文件
if (php_sapi_name() === 'cli') {
    NotificationTestRunner::run();
}
