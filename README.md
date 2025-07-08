# Hyperf Notification

一个专为 Hyperf 框架设计的通知系统，兼容 Laravel 通知的 API 设计，提供灵活、可扩展的通知发送功能。

## 特性

- 🚀 **高性能**: 基于 Hyperf 框架，支持异步队列处理
- 📧 **多渠道支持**: 邮件、数据库、广播等多种通知渠道
- 🔧 **易于扩展**: 支持自定义通知渠道
- 📝 **事件系统**: 完整的通知生命周期事件
- 🎯 **Laravel 兼容**: API 设计与 Laravel 通知保持一致
- 📊 **渠道响应**: 支持获取各渠道的发送结果
- 🎨 **模板支持**: 集成 Twig 模板引擎，支持邮件模板
- 🔄 **队列支持**: 支持异步队列处理，提高性能

## 支持的渠道

- **邮件 (Mail)**: 使用 Symfony Mailer 发送邮件
- **数据库 (Database)**: 将通知存储到数据库
- **广播 (Broadcast)**: 实时广播通知
- **自定义渠道**: 支持注册自定义通知渠道

## 环境要求

- PHP >= 8.2
- Hyperf >= 3.1.0
- MySQL/PostgreSQL/SQLite

## 安装

### 1. 通过 Composer 安装

```bash
composer require apffth/hyperf-notification
```

### 2. 发布配置文件

```bash
php bin/hyperf.php vendor:publish apffth/hyperf-notification
```

### 3. 运行数据库迁移

```bash
php bin/hyperf.php migrate
```

## 配置

### 基础配置

配置文件位于 `config/autoload/notification.php`：

```php
return [
    'queue' => [
        'queue' => env('NOTIFICATION_QUEUE', 'notification'),
        'delay' => (int) env('NOTIFICATION_QUEUE_DELAY', 0),
        'tries' => (int) env('NOTIFICATION_QUEUE_TRIES', 3),
    ],
    
    'events' => [
        'enabled' => env('NOTIFICATION_EVENTS_ENABLED', true),
        'enable_sending_event' => env('NOTIFICATION_ENABLE_SENDING_EVENT', true),
        'enable_sent_event' => env('NOTIFICATION_ENABLE_SENT_EVENT', true),
        'enable_failed_event' => env('NOTIFICATION_ENABLE_FAILED_EVENT', true),
        'log_events' => env('NOTIFICATION_LOG_EVENTS', true),
    ],
    
    'channels' => [
        'mail' => [
            'driver' => 'mail',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'notifications',
        ],
        'broadcast' => [
            'driver' => 'broadcast',
            'connection' => env('BROADCAST_CONNECTION', 'redis'),
        ],
    ],
];
```

### 邮件配置

在 `config/autoload/mail.php` 中配置邮件服务：

```php
return [
    'default_mailer' => env('MAIL_MAILER', 'smtp'),
    
    'mailers' => [
        'smtp' => [
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
        ],
    ],
    
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],
];
```

### Twig 配置

配置文件位于 `config/autoload/twig.php`：

```php
return [
    'paths' => [
        BASE_PATH . '/storage/emails',
    ],
    
    'options' => [
        'debug' => env('APP_DEBUG', false),
        'cache' => env('TWIG_CACHE', true),
        'cache_path' => BASE_PATH . '/runtime/twig/cache',
        'auto_reload' => env('TWIG_AUTO_RELOAD', true),
        'strict_variables' => true,
        'charset' => 'UTF-8',
        'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),
    ],
    
    'globals' => [
        'app_name' => env('APP_NAME', 'Hyperf App'),
    ],
];
```

## 使用方法

### 1. 创建通知类

```php
<?php

namespace App\Notifications;

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
            ->htmlTemplate('welcome.html.twig')
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
     */
    public function afterSend($notifiable): void
    {
        // 获取所有渠道的返回值
        $responses = $this->getChannelResponses();
        
        // 处理邮件渠道的返回值
        if ($this->hasChannelResponse('mail')) {
            $mailResponse = $this->getChannelResponse('mail');
            // 处理邮件发送结果
        }

        // 处理数据库渠道的返回值
        if ($this->hasChannelResponse('database')) {
            $dbResponse = $this->getChannelResponse('database');
            // 处理数据库存储结果
        }
    }
}
```

### 2. 在模型中使用 Notifiable trait

在你的模型类中使用 `Notifiable` trait：

```php
<?php

namespace App\Models;

use Apffth\Hyperf\Notification\Notifiable;
use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    use Notifiable;

    /**
     * 获取邮件地址
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }
}
```

### 3. 发送通知

```php
use App\Notifications\WelcomeNotification;

// 发送通知
$user = User::find(1);
$notification = new WelcomeNotification('张三', '欢迎使用我们的系统！');

$user->notify($notification);
```

### 4. 队列化通知

#### 方式一

```php
use App\Notifications\WelcomeNotification;

// 发送通知
$user = User::find(1);

$notification = new WelcomeNotification('张三', '欢迎使用我们的系统！');
$notification->delay(10);

$user->notify($notification);
```

#### 方式二

```php
use Apffth\Hyperf\Notification\Queueable;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('notifications')
             ->delay(60)
             ->tries(3);
    }
}
```

## 事件系统

### 注册事件监听器

```php
use Apffth\Hyperf\Notification\NotificationSender;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Apffth\Hyperf\Notification\Events\NotificationFailed;

// 发送前事件
NotificationSender::listen('notification.sending', function (NotificationSending $event) {
    echo "通知发送前: {$event->getChannel()}\n";
    
    // 根据条件阻止发送
    if ($event->getChannel() === 'mail' && $this->isMaintenanceMode()) {
        $event->preventSending();
    }
});

// 发送后事件
NotificationSender::listen('notification.sent', function (NotificationSent $event) {
    echo "通知发送成功: {$event->getChannel()}\n";
    echo "发送时间: " . $event->getSentAt()->format('Y-m-d H:i:s') . "\n";
});

// 失败事件
NotificationSender::listen('notification.failed', function (NotificationFailed $event) {
    echo "通知发送失败: {$event->getChannel()}\n";
    echo "错误信息: " . $event->getErrorMessage() . "\n";
});
```

### 基于类的事件监听器

```php
<?php

namespace App\Listeners;

use Apffth\Hyperf\Notification\Events\NotificationSent;
use Hyperf\Logger\LoggerFactory;

class LogNotificationSent
{
    public function __construct(private LoggerFactory $loggerFactory) {}

    public function handle(NotificationSent $event): void
    {
        $logger = $this->loggerFactory->get('notification');
        
        $logger->info('通知发送成功', [
            'channel' => $event->getChannel(),
            'notification' => get_class($event->getNotification()),
            'notifiable' => get_class($event->getNotifiable()),
            'sent_at' => $event->getSentAt()->format('Y-m-d H:i:s'),
        ]);
    }
}
```

## 自定义渠道

### 创建自定义渠道

```php
<?php

namespace App\Channels;

use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Notification;

class SlackChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification): mixed
    {
        $data = $notification->toSlack($notifiable);
        
        // 实现 Slack 发送逻辑
        $response = $this->sendToSlack($data);
        
        return [
            'success' => $response['ok'] ?? false,
            'channel' => $data['channel'] ?? 'general',
            'message' => $data['text'] ?? '',
            'sent_at' => date('Y-m-d H:i:s'),
        ];
    }
    
    protected function sendToSlack(array $data): array
    {
        // 实现具体的 Slack API 调用
        return ['ok' => true];
    }
}
```

### 注册自定义渠道

```php
use Apffth\Hyperf\Notification\NotificationSender;
use App\Channels\SlackChannel;

// 注册渠道类
NotificationSender::registerChannel('slack', SlackChannel::class);

// 或者注册渠道实例
NotificationSender::registerChannelInstance('slack', new SlackChannel());
```

## 邮件模板

### 创建 Twig 模板

在 `storage/emails/` 目录下创建模板文件：

```twig
{# storage/emails/welcome.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>欢迎邮件</title>
</head>
<body>
    <h1>欢迎 {{ userName }}！</h1>
    <p>{{ message }}</p>
    <p>感谢您使用我们的系统。</p>
</body>
</html>
```

```twig
{# storage/emails/welcome.txt.twig #}
欢迎 {{ userName }}！

{{ message }}

感谢您使用我们的系统。
```

### 在通知中使用模板

```php
public function toMail($notifiable): TemplatedEmail
{
    $email = new TemplatedEmail();
    $email->subject('欢迎 ' . $this->userName)
        ->htmlTemplate('welcome.html.twig')
        ->textTemplate('welcome.txt.twig')
        ->context([
            'userName' => $this->userName,
            'message' => $this->welcomeMessage,
        ]);

    return $email;
}
```

## 数据库通知

### 查询通知

```php
use Apffth\Hyperf\Notification\Models\Notification;

// 获取用户的所有通知
$notifications = $user->notifications;

// 获取未读通知
$unreadNotifications = $user->unreadNotifications;

// 获取已读通知
$readNotifications = $user->readNotifications;

// 标记所有通知为已读
$user->markNotificationsAsRead();

// 删除所有通知
$user->deleteNotifications();

// 标记单个通知为已读
$notification = Notification::find($id);
$notification->markAsRead();
```

### 在模型中添加通知关系

```php
class User extends Model
{
    use Notifiable;

    /**
     * 获取用户的通知
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    /**
     * 获取未读通知
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }
}
```

## 渠道响应处理

### 获取渠道响应

```php
class WelcomeNotification extends Notification
{
    public function afterSend($notifiable): void
    {
        // 获取所有渠道的返回值
        $responses = $this->getChannelResponses();
        
        // 获取指定渠道的返回值
        $mailResponse = $this->getChannelResponse('mail');
        $dbResponse = $this->getChannelResponse('database');
        
        // 检查是否有指定渠道的返回值
        if ($this->hasChannelResponse('mail')) {
            // 处理邮件响应
        }
        
        // 获取第一个渠道的返回值
        $firstResponse = $this->getFirstChannelResponse();
        
        // 检查是否所有渠道都发送成功
        $allSuccessful = $this->allChannelsSuccessful();
    }
}
```

## 测试

### 单元测试

```php
<?php

namespace Tests;

use App\Notifications\WelcomeNotification;
use App\Models\User;
use Hyperf\Testing\TestCase;

class NotificationTest extends TestCase
{
    public function testWelcomeNotification()
    {
        $user = new User(['email' => 'test@example.com']);
        $notification = new WelcomeNotification('测试用户');
        
        // 发送通知
        $user->notify($notification);
        
        // 验证通知发送结果
        $this->assertTrue($notification->allChannelsSuccessful());
        
        // 验证邮件渠道响应
        $mailResponse = $notification->getChannelResponse('mail');
        $this->assertTrue($mailResponse['success']);
    }
}
```

## 常见问题

### Q: 如何禁用某个渠道？

A: 在通知类的 `via` 方法中不返回该渠道名称，或者使用事件监听器阻止发送：

```php
NotificationSender::listen('notification.sending', function (NotificationSending $event) {
    if ($event->getChannel() === 'mail') {
        $event->preventSending();
    }
});
```

### Q: 如何自定义通知 ID？

A: 在通知类中重写 `setId` 方法：

```php
public function setId(): void
{
    $this->id = 'custom-' . uniqid();
}
```

### Q: 如何添加新的通知渠道？

A: 实现 `ChannelInterface` 接口，然后注册渠道：

```php
class CustomChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification): mixed
    {
        // 实现发送逻辑
        return ['success' => true];
    }
}

NotificationSender::registerChannel('custom', CustomChannel::class);
```

### Q: 如何配置邮件模板路径？

A: 在 `config/autoload/twig.php` 中配置模板路径：

```php
'paths' => [
    BASE_PATH . '/storage/emails',  // 邮件模板路径
    BASE_PATH . '/templates',       // 其他模板路径
],
```

### Q: 如何启用队列处理？

A: 在通知类中使用 `Queueable` trait 并实现队列配置：

```php
use Apffth\Hyperf\Notification\Queueable;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('notifications')
             ->delay(60)
             ->tries(3);
    }
}
```

## 许可证

本项目采用 MIT 许可证。详见 [LICENSE](LICENSE) 文件。 