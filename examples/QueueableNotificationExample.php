<?php

namespace Examples;

use Hyperf\Notification\Notification;
use Hyperf\Notification\Contracts\ShouldQueue;
use Hyperf\Notification\Queueable;
use Hyperf\Notification\Messages\MailMessage;

/**
 * 基础队列化通知
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('欢迎加入我们！')
            ->greeting('你好 ' . $notifiable->name)
            ->line('感谢您注册我们的应用。')
            ->action('访问网站', 'https://example.com');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => '欢迎加入我们！',
            'message' => '感谢您注册我们的应用。',
            'user_id' => $notifiable->id,
        ];
    }
}

/**
 * 高级队列化通知 - 自定义队列配置
 */
class AdvancedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // 队列配置
    public $queue = 'notifications'; // 指定队列名称
    public $delay = 60; // 延迟60秒执行
    public $tries = 3; // 最大重试次数
    public $timeout = 30; // 超时时间（秒）
    public $retryAfter = 10; // 重试间隔（秒）

    public function via($notifiable)
    {
        return ['mail', 'sms'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('高级通知')
            ->line('这是一个高级队列化通知。');
    }

    public function toSms($notifiable)
    {
        return [
            'content' => "你好 {$notifiable->name}，这是一个高级通知。",
            'template' => 'SMS_ADVANCED',
        ];
    }

    // 自定义失败处理
    public function failed(\Throwable $exception): void
    {
        // 记录失败日志
        echo "通知发送失败: " . $exception->getMessage() . "\n";

        // 可以发送到备用渠道或记录到数据库
        $this->sendToFallbackChannel();
    }

    // 条件队列化
    public function shouldQueue($notifiable): bool
    {
        // 只在特定条件下队列化
        return $notifiable->email && !$notifiable->is_test_user;
    }

    // 条件发送
    public function shouldSend($notifiable): bool
    {
        // 只在特定条件下发送
        return $notifiable->email_verified_at !== null;
    }

    // 发送到备用渠道
    protected function sendToFallbackChannel(): void
    {
        echo "发送到备用渠道\n";
    }
}

/**
 * 延迟通知
 */
class DelayedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $delay = 300; // 延迟5分钟

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('延迟通知')
            ->line('这是一个延迟发送的通知。')
            ->line('发送时间: ' . date('Y-m-d H:i:s'));
    }
}

/**
 * 高优先级通知
 */
class HighPriorityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $queue = 'high-priority'; // 使用高优先级队列

    public function via($notifiable)
    {
        return ['mail', 'sms'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('高优先级通知')
            ->line('这是一个高优先级通知。');
    }

    public function toSms($notifiable)
    {
        return [
            'content' => "紧急通知：{$notifiable->name}，请立即处理。",
            'template' => 'SMS_URGENT',
        ];
    }
}

/**
 * 批量通知
 */
class BatchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $queue = 'batch-notifications';

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('批量通知')
            ->line('这是一个批量处理的通知。');
    }

    // 批量通知总是队列化
    public function shouldQueue($notifiable): bool
    {
        return true;
    }
}

/**
 * 测试用户类
 */
class TestUser
{
    use \Hyperf\Notification\Notifiable;

    public $id;
    public $name;
    public $email;
    public $phone;
    public $email_verified_at;
    public $is_test_user = false;

    public function __construct($id = 1, $name = '张三', $email = 'zhangsan@example.com')
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->phone = '13800138000';
        $this->email_verified_at = date('Y-m-d H:i:s');
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
            default:
                return $this;
        }
    }
}

/**
 * 队列化通知示例
 * 参考 Laravel 的队列化通知实现
 */
class QueueableNotificationExample
{
    /**
     * 使用示例
     */
    public static function run()
    {
        echo "=== 队列化通知示例 ===\n\n";

        $user = new TestUser();

        echo "1. 发送基础队列化通知...\n";
        $user->notify(new WelcomeNotification());
        echo "✅ 基础通知已推送到队列\n\n";

        echo "2. 发送高级队列化通知...\n";
        $user->notify(new AdvancedNotification());
        echo "✅ 高级通知已推送到 notifications 队列\n\n";

        echo "3. 发送延迟通知...\n";
        $user->notify(new DelayedNotification());
        echo "✅ 延迟通知已推送到队列，将在5分钟后发送\n\n";

        echo "4. 发送高优先级通知...\n";
        $user->notify(new HighPriorityNotification());
        echo "✅ 高优先级通知已推送到 high-priority 队列\n\n";

        echo "5. 发送批量通知...\n";
        $user->notify(new BatchNotification());
        echo "✅ 批量通知已推送到 batch-notifications 队列\n\n";

        echo "=== 队列化通知示例完成 ===\n";
        echo "请启动队列处理器来处理这些通知：\n";
        echo "php bin/hyperf.php process:start AsyncQueueConsumer --queue=default\n";
        echo "php bin/hyperf.php process:start AsyncQueueConsumer --queue=notifications\n";
        echo "php bin/hyperf.php process:start AsyncQueueConsumer --queue=high-priority\n";
        echo "php bin/hyperf.php process:start AsyncQueueConsumer --queue=batch-notifications\n";
    }
}

// 如果直接运行此文件，执行示例
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    QueueableNotificationExample::run();
}
