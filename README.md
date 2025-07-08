# Hyperf Notification

参考 Laravel Notifications 实现的 Hyperf 通知系统。

## 功能特性

- ✅ 支持多种通知渠道（邮件、数据库、广播、Slack等）
- ✅ 队列化通知支持
- ✅ 通知事件系统（具有健壮性，支持无日志环境）
- ✅ 数据库通知存储
- ✅ 与 Laravel Notification API 完全兼容
- ✅ 支持通知路由
- ✅ 支持通知标记（已读/未读）
- ✅ 支持 notifiable 别名，保持数据库类型标识一致
- ✅ 支持自定义通知渠道
- ✅ 事件系统容错处理，支持各种环境配置

## 安装

### 方法一：通过 Composer 安装（推荐）

```bash
composer require apffth/hyperf-notification
```

### 方法二：本地开发安装

1. 将本包复制到你的 Hyperf 项目中
2. 在 `composer.json` 中添加：

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./hyperf-notification"
        }
    ],
    "require": {
        "apffth/hyperf-notification": "*"
    }
}
```

3. 运行安装：
```bash
composer update
```

## 配置

### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish apffth/hyperf-notification
```

### 2. 运行数据库迁移

```bash
php bin/hyperf.php migrate
```

### 3. 配置环境变量

在 `.env` 文件中添加：

```env
# 通知配置
NOTIFICATION_DEFAULT_CHANNEL=mail
NOTIFICATION_QUEUE=notifications

# 队列配置
NOTIFICATION_QUEUE_CONNECTION=default
NOTIFICATION_QUEUE_DELAY=0
NOTIFICATION_QUEUE_TRIES=3
NOTIFICATION_QUEUE_TIMEOUT=30
NOTIFICATION_QUEUE_RETRY_AFTER=10
NOTIFICATION_QUEUE_PRIORITY=0

# 事件配置
NOTIFICATION_EVENTS_ENABLED=true
NOTIFICATION_ENABLE_SENDING_EVENT=true
NOTIFICATION_ENABLE_SENT_EVENT=true
NOTIFICATION_ENABLE_FAILED_EVENT=true
NOTIFICATION_LOG_EVENTS=true

# 邮件配置
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_EHLO_DOMAIN=example.com
```

### 4. 配置文件说明

配置文件 `config/notification.php` 包含以下配置项：

```php
// 获取配置示例
$queueConfig = config('notification.queue');
$events = config('notification.events');

// 队列配置
$connection = $queueConfig['connection']; // 队列连接
$queue = $queueConfig['queue'];           // 队列名称
$delay = $queueConfig['delay'];           // 延迟时间
$tries = $queueConfig['tries'];           // 重试次数
$timeout = $queueConfig['timeout'];       // 超时时间
$retryAfter = $queueConfig['retry_after']; // 重试间隔
$priority = $queueConfig['priority'];     // 优先级

// 事件配置
$enabled = $events['enabled'];                    // 是否启用事件系统
$enableSending = $events['enable_sending_event']; // 是否启用发送前事件
$enableSent = $events['enable_sent_event'];       // 是否启用发送后事件
$enableFailed = $events['enable_failed_event'];   // 是否启用失败事件
$logEvents = $events['log_events'];               // 是否记录事件日志
```

## 使用方法

### 1. 在模型中添加 Notifiable trait

```php
<?php

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Apffth\Hyperf\Notification\Notifiable;

class User extends Model
{
    use Notifiable;

    // 你的模型代码...
}
```

### 2. 创建通知类

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Apffth\Hyperf\Notification\Messages\MailMessage;

class WelcomeNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('欢迎加入我们！')
            ->greeting('你好 ' . $notifiable->name)
            ->line('感谢您注册我们的应用。')
            ->action('访问网站', 'https://example.com')
            ->line('如果您有任何问题，请随时联系我们。');
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
```

### 3. 发送通知

```php
<?php

namespace App\Controller;

use App\Model\User;
use App\Notification\WelcomeNotification;

class UserController
{
    public function register()
    {
        // 创建用户
        $user = User::create([
            'name' => '张三',
            'email' => 'zhangsan@example.com',
        ]);

        // 发送欢迎通知
        $user->notify(new WelcomeNotification());

        return '注册成功！';
    }
}
```

### 4. 事件系统

Hyperf Notification 提供了健壮的事件系统，支持通知发送前、发送后和失败事件。事件系统具有以下特性：

#### 4.1 事件系统健壮性

事件系统经过优化，具有良好的容错能力：

- **无日志环境支持**：即使容器中没有配置 LoggerFactory，事件系统仍能正常工作
- **日志记录容错**：如果日志记录失败，不会影响事件分发和通知发送
- **动态配置**：支持运行时启用/禁用事件系统
- **手动日志配置**：支持手动设置日志实例

#### 4.2 事件类型

```php
// 通知发送前事件
'notification.sending' => NotificationSending::class

// 通知发送后事件  
'notification.sent' => NotificationSent::class

// 通知发送失败事件
'notification.failed' => NotificationFailed::class
```

#### 4.3 事件监听器示例

```php
<?php

namespace App\Listeners;

use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Apffth\Hyperf\Notification\Events\NotificationFailed;

class NotificationEventListener
{
    public function handleSending(NotificationSending $event): void
    {
        // 在通知发送前执行
        $notification = $event->getNotification();
        $notifiable = $event->getNotifiable();
        $channel = $event->getChannel();
        
        // 可以在这里阻止通知发送
        // $event->preventSending();
        
        echo "准备通过 {$channel} 渠道发送通知给 " . get_class($notifiable);
    }
    
    public function handleSent(NotificationSent $event): void
    {
        // 在通知发送后执行
        if ($event->wasSuccessful()) {
            echo "通知发送成功";
        }
    }
    
    public function handleFailed(NotificationFailed $event): void
    {
        // 在通知发送失败时执行
        echo "通知发送失败: " . $event->getErrorMessage();
    }
}
```

#### 4.4 注册事件监听器

```php
<?php

use Apffth\Hyperf\Notification\NotificationSender;

// 注册事件监听器
NotificationSender::listen('notification.sending', [NotificationEventListener::class, 'handleSending']);
NotificationSender::listen('notification.sent', [NotificationEventListener::class, 'handleSent']);
NotificationSender::listen('notification.failed', [NotificationEventListener::class, 'handleFailed']);
```

#### 4.5 不同环境下的使用

**开发环境（有完整日志系统）：**
```php
// 自动获取 LoggerFactory，正常记录日志
$dispatcher = new EventDispatcher($container, true);
```

**测试环境（无日志系统）：**
```php
// 没有 LoggerFactory，但事件系统正常工作
$dispatcher = new EventDispatcher($container, true);
// $dispatcher->getLogger() 返回 null，但事件分发正常
```

**生产环境（手动配置日志）：**
```php
// 手动设置日志实例
$dispatcher = new EventDispatcher($container, true);
$dispatcher->setLogger($customLogger);
```

### 5. 邮件通知功能

Hyperf Notification 支持使用 Symfony Mailer 发送邮件通知，支持 SMTP 协议和 Twig 模板引擎。

#### 4.1 邮件配置

确保在 `.env` 文件中配置了正确的邮件设置：

```env
# 邮件服务配置
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Your App Name"
MAIL_EHLO_DOMAIN=yourdomain.com

# Twig 模板引擎配置
APP_DEBUG=true
TWIG_CACHE=false
APP_TIMEZONE=Asia/Shanghai
APP_NAME="Your App Name"
APP_URL=http://localhost:9501
```

#### 4.2 Twig 配置

##### 4.2.1 安装 Twig 依赖

```bash
composer require twig/twig
```

##### 4.2.2 Twig 配置文件

系统会自动发布 `config/autoload/twig.php` 配置文件，您可以根据需要修改：

```php
<?php

declare(strict_types=1);

return [
    // 模板路径配置
    'paths' => [
        'emails' => BASE_PATH . '/templates/emails',
        'views' => BASE_PATH . '/templates/views',
    ],
    
    // Twig 环境选项
    'options' => [
        'debug' => env('APP_DEBUG', false),
        'cache' => env('TWIG_CACHE', true),
        'cache_path' => BASE_PATH . '/runtime/twig/cache',
        'auto_reload' => env('APP_DEBUG', false),
        'strict_variables' => env('APP_DEBUG', false),
        'charset' => 'UTF-8',
        'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),
    ],
    
    // 全局变量
    'globals' => [
        'app_name' => env('APP_NAME', 'Hyperf App'),
        'app_url' => env('APP_URL', 'http://localhost'),
        'app_version' => env('APP_VERSION', '1.0.0'),
    ],
];
```

#### 4.3 创建邮件通知

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class WelcomeEmailNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): TemplatedEmail
    {
        $email = new TemplatedEmail();
        $email
            ->subject('欢迎加入我们的平台！')
            ->htmlTemplate('emails/welcome.html.twig')
            ->textTemplate('emails/welcome.txt.twig')
            ->context([
                'userName' => $notifiable->name,
                'message' => '感谢您注册我们的平台！',
                'login_url' => config('twig.globals.app_url') . '/login',
                'forgot_password_url' => config('twig.globals.app_url') . '/forgot-password',
            ]);

        return $email;
    }
}
```

#### 4.3 使用 Twig 模板

##### 4.3.1 模板目录结构

```
templates/
├── emails/
│   ├── layout.html.twig          # 邮件布局模板
│   ├── welcome.html.twig         # 欢迎邮件 HTML 版本
│   ├── welcome.txt.twig          # 欢迎邮件纯文本版本
│   ├── password-reset.html.twig  # 密码重置邮件 HTML 版本
│   ├── password-reset.txt.twig   # 密码重置邮件纯文本版本
│   └── ...
└── views/
    └── ...                       # 其他视图模板
```

##### 4.3.2 邮件布局模板

创建 `templates/emails/layout.html.twig`：

```twig
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}{{ app_name }}{% endblock %}</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .content {
            margin-bottom: 30px;
        }
        .footer {
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>{{ app_name }}</h1>
        </div>
        
        <div class="content">
            {% block content %}{% endblock %}
        </div>
        
        <div class="footer">
            <p>此邮件由 {{ app_name }} 系统自动发送</p>
            <p>如果您有任何问题，请联系我们的客服团队</p>
            <p>&copy; {{ "now"|date("Y") }} {{ app_name }}. 保留所有权利。</p>
        </div>
    </div>
</body>
</html>
```

##### 4.3.3 欢迎邮件模板

创建 HTML 模板 `templates/emails/welcome.html.twig`：

```twig
{% extends "emails/layout.html.twig" %}

{% block title %}欢迎加入 {{ app_name }}{% endblock %}

{% block content %}
    <h2>欢迎 {{ userName }}！</h2>
    
    <div class="alert alert-success">
        <p>{{ message }}</p>
    </div>
    
    <p>感谢您注册我们的平台。我们很高兴您能加入我们！</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ login_url }}" class="button">立即登录</a>
    </div>
    
    <p>如果您在登录过程中遇到任何问题，请点击下面的链接重置密码：</p>
    <div style="text-align: center;">
        <a href="{{ forgot_password_url }}" class="button">忘记密码？</a>
    </div>
    
    <p>再次感谢您选择 {{ app_name }}！</p>
{% endblock %}
```

创建纯文本模板 `templates/emails/welcome.txt.twig`：

```twig
欢迎 {{ userName }}！

{{ message }}

感谢您注册我们的平台。我们很高兴您能加入我们！

立即登录：{{ login_url }}

如果您在登录过程中遇到任何问题，请访问：{{ forgot_password_url }}

再次感谢您选择 {{ app_name }}！

---
此邮件由 {{ app_name }} 系统自动发送
如果您有任何问题，请联系我们的客服团队
© {{ "now"|date("Y") }} {{ app_name }}. 保留所有权利。
```

#### 4.4 发送邮件通知

```php
// 发送邮件通知
$user->notify(new WelcomeEmailNotification());
```

#### 4.5 邮件路由

如果您的用户模型没有 `email` 属性，可以通过 `routeNotificationFor` 方法指定邮件地址：

```php
<?php

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Apffth\Hyperf\Notification\Notifiable;

class User extends Model
{
    use Notifiable;

    /**
     * 获取指定渠道的通知路由
     */
    public function routeNotificationFor($driver, $notification = null)
    {
        if ($driver === 'mail') {
            return $this->email_address; // 使用自定义的邮箱字段
        }

        return parent::routeNotificationFor($driver, $notification);
    }
}
```

#### 4.6 邮件通知示例

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class PasswordResetNotification extends Notification
{
    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): TemplatedEmail
    {
        $email = new TemplatedEmail();
        $email
            ->subject('密码重置请求')
            ->htmlTemplate('emails/password-reset.html.twig')
            ->textTemplate('emails/password-reset.txt.twig')
            ->context([
                'user' => $notifiable,
                'reset_url' => 'https://example.com/reset-password?token=' . $this->token,
                'expires_in' => '60分钟',
            ]);

        return $email;
    }
}

// 使用示例
$user->notify(new PasswordResetNotification($resetToken));
```

### 6. 查询通知

```php
// 获取所有通知
$notifications = $user->notifications;

// 获取未读通知
$unreadNotifications = $user->unreadNotifications;

// 获取已读通知
$readNotifications = $user->readNotifications;

// 标记通知为已读
$user->markNotificationsAsRead();

// 删除所有通知
$user->deleteNotifications();
```

### 5. 渠道返回值支持

Hyperf Notification 支持获取渠道 `send()` 方法的返回值，让你可以在业务代码中处理发送结果。

#### 5.1 渠道返回值类型

每个渠道都会返回相应的发送结果信息：

**邮件渠道返回值：**
```php
[
    'success' => true,
    'message_id' => '<message-id@example.com>',
    'to' => 'user@example.com',
    'subject' => '邮件主题',
    'sent_at' => '2024-01-01 12:00:00',
]
```

**数据库渠道返回值：**
```php
[
    'success' => true,
    'notification_id' => 'uuid-string',
    'notifiable_type' => 'App\\Model\\User',
    'notifiable_id' => 1,
    'type' => 'App\\Notification\\WelcomeNotification',
    'created_at' => '2024-01-01 12:00:00',
]
```

**广播渠道返回值：**
```php
[
    'success' => true,
    'channel' => 'App.Models.User.1',
    'event' => 'App\\Notification\\WelcomeNotification',
    'data' => [...],
    'broadcasted_at' => '2024-01-01 12:00:00',
]
```

#### 5.2 在通知类中获取渠道返回值

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class WelcomeNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): TemplatedEmail
    {
        $email = new TemplatedEmail();
        $email->subject('欢迎加入我们！')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $notifiable,
                'message' => '感谢您注册我们的平台！',
            ]);

        return $email;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => '欢迎加入我们！',
            'user_name' => $notifiable->name,
            'type' => 'welcome',
        ];
    }

    /**
     * 通知发送完成后的回调方法
     * 可以在这里处理渠道返回值
     */
    public function afterSend($notifiable): void
    {
        // 获取所有渠道的返回值
        $responses = $this->getChannelResponses();
        
        // 处理邮件渠道的返回值
        if ($this->hasChannelResponse('mail')) {
            $mailResponse = $this->getChannelResponse('mail');
            $this->logEmailSent($notifiable, $mailResponse);
        }

        // 处理数据库渠道的返回值
        if ($this->hasChannelResponse('database')) {
            $dbResponse = $this->getChannelResponse('database');
            $this->logNotificationCreated($notifiable, $dbResponse);
        }
    }

    protected function logEmailSent($notifiable, array $response): void
    {
        // 记录邮件发送日志
        $logData = [
            'user_id' => $notifiable->getKey(),
            'email' => $response['to'],
            'message_id' => $response['message_id'],
            'sent_at' => $response['sent_at'],
        ];
        
        // 写入日志或数据库
        \Hyperf\Utils\ApplicationContext::getContainer()
            ->get(\Psr\Log\LoggerInterface::class)
            ->info('邮件发送成功', $logData);
    }

    protected function logNotificationCreated($notifiable, array $response): void
    {
        // 记录通知创建日志
        $logData = [
            'user_id' => $notifiable->getKey(),
            'notification_id' => $response['notification_id'],
            'type' => $response['type'],
        ];
        
        \Hyperf\Utils\ApplicationContext::getContainer()
            ->get(\Psr\Log\LoggerInterface::class)
            ->info('通知创建成功', $logData);
    }
}
```

#### 5.3 在业务代码中获取渠道返回值

```php
<?php

namespace App\Controller;

use App\Model\User;
use App\Notification\WelcomeNotification;
use Apffth\Hyperf\Notification\NotificationSender;

class UserController
{
    public function register()
    {
        // 创建用户
        $user = User::create([
            'name' => '张三',
            'email' => 'zhangsan@example.com',
        ]);

        // 创建通知
        $notification = new WelcomeNotification();

        // 发送通知
        NotificationSender::send($user, $notification);

        // 获取所有渠道的返回值
        $responses = $notification->getChannelResponses();
        
        // 处理返回值
        $this->handleChannelResponses($responses, $user);

        return '注册成功！';
    }

    protected function handleChannelResponses(array $responses, User $user): void
    {
        foreach ($responses as $channel => $response) {
            switch ($channel) {
                case 'mail':
                    if ($response['success']) {
                        // 邮件发送成功，更新用户状态
                        $user->update(['email_sent_at' => now()]);
                    }
                    break;
                    
                case 'database':
                    if ($response['success']) {
                        // 数据库通知创建成功，增加通知计数
                        $user->increment('notification_count');
                    }
                    break;
            }
        }
    }
}
```

#### 5.4 渠道返回值相关方法

Notification 基类提供了以下方法来处理渠道返回值：

```php
// 获取所有渠道的返回值
$responses = $notification->getChannelResponses();

// 获取指定渠道的返回值
$mailResponse = $notification->getChannelResponse('mail');

// 检查是否有指定渠道的返回值
if ($notification->hasChannelResponse('database')) {
    // 处理数据库渠道返回值
}

// 获取第一个渠道的返回值
$firstResponse = $notification->getFirstChannelResponse();

// 检查是否所有渠道都发送成功
if ($notification->allChannelsSuccessful()) {
    echo '所有渠道都发送成功！';
}

// 获取第一个成功的渠道响应
$successResponse = $notification->getFirstSuccessfulResponse();
```

#### 5.5 自定义渠道返回值

如果你创建了自定义渠道，可以返回任何有意义的数据：

```php
<?php

namespace App\Notification\Channels;

use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Notification;

class SmsChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification): mixed
    {
        $message = $notification->toSms($notifiable);
        
        // 发送短信的逻辑...
        $result = $this->sendSms($message);
        
        // 返回短信发送结果
        return [
            'success' => $result['success'],
            'message_id' => $result['message_id'],
            'phone' => $this->getPhone($notifiable),
            'sent_at' => date('Y-m-d H:i:s'),
            'cost' => $result['cost'] ?? 0,
        ];
    }
}
```

### 6. 查询通知

为了保持数据库中的 `notifiable_type` 字段一致，即使类名发生变化，你可以使用别名功能。系统会按以下优先级查找别名：

1. `getMorphClass()` 方法（已取消）
2. `morphClass` 属性
3. `MORPH_CLASS` 常量（已取消）
4. 默认使用类名

#### 方式一：使用 getMorphClass() 方法

```php
<?php

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Apffth\Hyperf\Notification\Notifiable;

class User extends Model
{
    use Notifiable;

    /**
     * 返回用于多态关联的类名别名
     */
    public function getMorphClass(): string
    {
        return 'App\\Models\\User'; // 使用别名而不是实际的类名
    }
}
```

#### 方式二：使用 morphClass 属性

```php
<?php

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Apffth\Hyperf\Notification\Notifiable;

class User extends Model
{
    use Notifiable;

    /**
     * 指定多态关联的类名别名
     */
    protected string $morphClass = 'App\\Models\\User';
}
```

#### 方式三：使用 MORPH_CLASS 常量

```php
<?php

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Apffth\Hyperf\Notification\Notifiable;

class User extends Model
{
    use Notifiable;

    /**
     * 指定多态关联的类名别名
     */
    public const MORPH_CLASS = 'App\\Models\\User';
}
```

这样，无论你的类名如何变化，数据库中的 `notifiable_type` 都会保持为 `App\Models\User`，确保数据的一致性和可维护性。

### 8. 队列化通知

参考 [Laravel 11 的队列化通知](https://laravel.com/docs/11.x/notifications#queueing-notifications)，Hyperf Notification 支持通过队列异步发送通知，提高应用性能。

#### 基础队列化通知

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Apffth\Hyperf\Notification\Contracts\ShouldQueue;
use Apffth\Hyperf\Notification\Queueable;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('欢迎加入我们！')
            ->line('感谢您注册我们的应用。');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => '欢迎加入我们！',
            'message' => '感谢您注册我们的应用。',
        ];
    }
}
```

#### 高级队列化通知

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Apffth\Hyperf\Notification\Contracts\ShouldQueue;
use Apffth\Hyperf\Notification\Queueable;

class AdvancedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // 队列配置 - 在子类中定义这些属性
    public ?string $queue = 'notifications'; // 指定队列名称
    public ?int $delay = 60; // 延迟60秒执行
    public ?int $tries = 3; // 最大重试次数
    public ?int $timeout = 30; // 超时时间（秒）
    public ?int $retryAfter = 10; // 重试间隔（秒）
    public ?string $connection = 'default'; // 队列连接
    public ?int $priority = 0; // 队列优先级

    public function via($notifiable)
    {
        return ['mail', 'sms'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
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
        logger()->error('通知发送失败', [
            'notification' => get_class($this),
            'exception' => $exception->getMessage(),
        ]);
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
}
```

#### 延迟通知

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Apffth\Hyperf\Notification\Contracts\ShouldQueue;
use Apffth\Hyperf\Notification\Queueable;

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
        return (new MailMessage)
            ->subject('延迟通知')
            ->line('这是一个延迟发送的通知。');
    }
}
```

#### 高优先级通知

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Apffth\Hyperf\Notification\Contracts\ShouldQueue;
use Apffth\Hyperf\Notification\Queueable;

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
        return (new MailMessage)
            ->subject('高优先级通知')
            ->line('这是一个高优先级通知。');
    }
}
```

#### 发送队列化通知

```php
<?php

namespace App\Controller;

use App\Model\User;
use App\Notification\WelcomeNotification;

class UserController
{
    public function register()
    {
        // 创建用户
        $user = User::create([
            'name' => '张三',
            'email' => 'zhangsan@example.com',
        ]);

        // 发送队列化通知（异步）
        $user->notify(new WelcomeNotification());

        // 立即返回响应，通知在后台处理
        return '注册成功！通知将在后台发送。';
    }

    public function sendAdvancedNotification()
    {
        $user = User::find(1);
        
        // 使用 Laravel 11 风格的链式调用
        $notification = new WelcomeNotification();
        $notification->onQueue('urgent')
                    ->delay(0)
                    ->tries(5)
                    ->timeout(60)
                    ->retryAfter(30)
                    ->onConnection('redis')
                    ->priority(10);
        
        $user->notify($notification);
    }
}

#### Laravel 11 兼容特性

Hyperf Notification 完全兼容 Laravel 11 的队列化通知 API：

- ✅ **ShouldQueue 接口**: 实现此接口使通知自动队列化
- ✅ **Queueable trait**: 提供队列配置方法
- ✅ **链式调用**: 支持 `onQueue()`, `delay()`, `tries()`, `timeout()`, `retryAfter()`, `onConnection()`, `priority()` 等方法
- ✅ **失败处理**: `failed()` 方法处理发送失败
- ✅ **条件队列化**: `shouldQueue()` 方法控制是否队列化
- ✅ **条件发送**: `shouldSend()` 方法控制是否发送
- ✅ **事件系统**: 支持 `NotificationSending` 和 `NotificationSent` 事件
```

#### 配置队列系统

在 `config/autoload/async_queue.php` 中配置队列：

```php
<?php

return [
    'default' => [
        'driver' => 'redis',
        'redis' => [
            'pool' => 'default',
        ],
        'channel' => 'queue',
        'timeout' => 2,
        'retry_seconds' => 5,
        'handle_timeout' => 10,
        'processes' => 1,
        'concurrent' => [
            'limit' => 10,
        ],
    ],
    'notifications' => [
        'driver' => 'redis',
        'redis' => [
            'pool' => 'default',
        ],
        'channel' => 'notifications',
        'timeout' => 5,
        'retry_seconds' => 10,
        'handle_timeout' => 30,
        'processes' => 2,
        'concurrent' => [
            'limit' => 5,
        ],
    ],
    'high-priority' => [
        'driver' => 'redis',
        'redis' => [
            'pool' => 'default',
        ],
        'channel' => 'high-priority',
        'timeout' => 2,
        'retry_seconds' => 3,
        'handle_timeout' => 15,
        'processes' => 1,
        'concurrent' => [
            'limit' => 3,
        ],
    ],
];
```

#### 启动队列处理器

```bash
# 启动默认队列处理器
php bin/hyperf.php process:start AsyncQueueConsumer

# 启动通知专用队列处理器
php bin/hyperf.php process:start AsyncQueueConsumer --queue=notifications

# 启动高优先级队列处理器
php bin/hyperf.php process:start AsyncQueueConsumer --queue=high-priority

# 启动多个处理器
php bin/hyperf.php process:start AsyncQueueConsumer --queue=notifications --processes=2
```

#### 队列化通知的优势

1. **提高响应速度**：用户请求立即返回，不需要等待通知发送完成
2. **提高可靠性**：失败的通知可以重试
3. **提高性能**：避免阻塞主线程
4. **支持高并发**：大量通知可以排队处理
5. **灵活配置**：支持延迟、重试、超时等配置

## 事件系统

Hyperf Notification 提供了完整的事件系统，让你可以在通知发送的不同阶段执行自定义逻辑。

### 事件类型

系统提供以下三种事件：

1. **NotificationSending** - 通知发送前事件
2. **NotificationSent** - 通知发送后事件  
3. **NotificationFailed** - 通知失败事件

### 基础事件监听

```php
<?php

use Apffth\Hyperf\Notification\NotificationSender;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Apffth\Hyperf\Notification\Events\NotificationFailed;

// 注册事件监听器
NotificationSender::listen('notification.sending', function (NotificationSending $event) {
    echo "通知发送前: {$event->getChannel()}\n";
    
    // 可以阻止发送
    if ($event->getChannel() === 'mail' && $this->isMaintenanceMode()) {
        $event->preventSending();
    }
});

NotificationSender::listen('notification.sent', function (NotificationSent $event) {
    echo "通知发送后: {$event->getChannel()}\n";
    echo "发送成功: " . ($event->wasSuccessful() ? '是' : '否') . "\n";
});

NotificationSender::listen('notification.failed', function (NotificationFailed $event) {
    echo "通知失败: {$event->getChannel()}\n";
    echo "错误信息: " . $event->getErrorMessage() . "\n";
});
```

### 基于类的事件监听器

```php
<?php

namespace App\Listeners;

use Apffth\Hyperf\Notification\Events\NotificationSending;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class LogNotificationSending
{
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('notification');
    }

    public function handle(NotificationSending $event): void
    {
        $this->logger->info('Notification sending', [
            'notifiable' => $this->getNotifiableInfo($event->getNotifiable()),
            'notification' => get_class($event->getNotification()),
            'channel' => $event->getChannel(),
        ]);
    }

    protected function getNotifiableInfo($notifiable): array
    {
        if (is_object($notifiable)) {
            return [
                'type' => get_class($notifiable),
                'id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
            ];
        }

        return [
            'type' => gettype($notifiable),
            'value' => is_scalar($notifiable) ? $notifiable : null,
        ];
    }
}
```

### 条件发送控制

```php
<?php

NotificationSender::listen('notification.sending', function (NotificationSending $event) {
    $notifiable = $event->getNotifiable();
    $notification = $event->getNotification();
    
    // 只在工作时间发送邮件
    if ($event->getChannel() === 'mail' && !$this->isWorkingHours()) {
        $event->preventSending();
    }
    
    // 检查用户是否允许接收通知
    if (method_exists($notifiable, 'shouldReceiveNotifications') && !$notifiable->shouldReceiveNotifications()) {
        $event->preventSending();
    }
    
    // 检查通知频率限制
    if ($this->isRateLimited($notifiable, $notification)) {
        $event->preventSending();
    }
});
```

### 发送后操作

```php
<?php

NotificationSender::listen('notification.sent', function (NotificationSent $event) {
    if ($event->wasSuccessful()) {
        // 更新用户通知统计
        $notifiable = $event->getNotifiable();
        if (method_exists($notifiable, 'incrementNotificationCount')) {
            $notifiable->incrementNotificationCount();
        }
        
        // 发送到外部系统
        $this->sendToExternalSystem($event);
        
        // 记录通知历史
        $this->logNotificationHistory($event);
    }
});
```

### 失败处理

```php
<?php

NotificationSender::listen('notification.failed', function (NotificationFailed $event) {
    // 记录失败日志
    $this->logFailure($event);
    
    // 发送告警
    $this->sendAlert($event);
    
    // 重试逻辑
    $this->handleRetry($event);
});
```

### 事件配置

在 `config/notification.php` 中配置事件：

```php
'events' => [
    // 是否启用事件系统
    'enabled' => env('NOTIFICATION_EVENTS_ENABLED', true),
    
    // 是否启用发送前事件
    'enable_sending_event' => env('NOTIFICATION_ENABLE_SENDING_EVENT', true),
    
    // 是否启用发送后事件
    'enable_sent_event' => env('NOTIFICATION_ENABLE_SENT_EVENT', true),
    
    // 是否启用失败事件
    'enable_failed_event' => env('NOTIFICATION_ENABLE_FAILED_EVENT', true),
    
    // 是否记录事件日志
    'log_events' => env('NOTIFICATION_LOG_EVENTS', true),
    
    // 事件监听器配置
    'listeners' => [
        // 可以在这里配置全局事件监听器
        // 'notification.sending' => [
        //     \App\Listeners\LogNotificationSending::class,
        // ],
        // 'notification.sent' => [
        //     \App\Listeners\LogNotificationSent::class,
        // ],
        // 'notification.failed' => [
        //     \App\Listeners\LogNotificationFailed::class,
        // ],
    ],
],
```

### 事件属性

#### NotificationSending 事件

- `getNotifiable()` - 获取通知接收者
- `getNotification()` - 获取通知实例
- `getChannel()` - 获取通知渠道
- `preventSending()` - 阻止发送
- `shouldSend()` - 检查是否应该发送

#### NotificationSent 事件

- `getNotifiable()` - 获取通知接收者
- `getNotification()` - 获取通知实例
- `getChannel()` - 获取通知渠道
- `getResponse()` - 获取渠道响应结果
- `getSentAt()` - 获取发送时间
- `wasSuccessful()` - 检查是否发送成功

#### NotificationFailed 事件

- `getNotifiable()` - 获取通知接收者
- `getNotification()` - 获取通知实例
- `getChannel()` - 获取通知渠道
- `getException()` - 获取异常信息
- `getFailedAt()` - 获取失败时间
- `getErrorMessage()` - 获取错误消息
- `getErrorCode()` - 获取错误代码

### 7. 自定义通知渠道

系统支持注册自定义通知渠道，让你可以轻松扩展通知功能。

### 1. 创建自定义渠道

自定义渠道需要实现 `ChannelInterface` 接口：

```php
<?php

namespace App\Channels;

use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Notification;

class SmsChannel implements ChannelInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'api_key' => '',
            'api_secret' => '',
        ], $config);
    }

    public function send($notifiable, Notification $notification)
    {
        // 获取短信内容
        $message = $notification->toSms($notifiable);
        
        // 获取手机号码
        $phone = $this->getPhoneNumber($notifiable);
        
        // 发送短信
        $this->sendSms($phone, $message);
    }

    protected function getPhoneNumber($notifiable): ?string
    {
        // 从 notifiable 获取手机号码
        if (method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable->routeNotificationFor('sms');
        }

        return $notifiable->phone ?? null;
    }

    protected function sendSms(string $phone, array $message): void
    {
        // 实现短信发送逻辑
        // 例如调用阿里云短信服务、腾讯云短信服务等
    }
}
```

### 2. 注册自定义渠道

#### 方式一：注册渠道类

```php
use Apffth\Hyperf\Notification\NotificationSender;
use App\Channels\SmsChannel;

// 注册渠道类
NotificationSender::registerChannel('sms', SmsChannel::class);
```

#### 方式二：注册渠道实例

```php
use Apffth\Hyperf\Notification\NotificationSender;
use App\Channels\SmsChannel;

// 创建渠道实例
$smsChannel = new SmsChannel([
    'api_key' => 'your_api_key',
    'api_secret' => 'your_api_secret',
]);

// 注册渠道实例
NotificationSender::registerChannelInstance('sms', $smsChannel);
```

### 3. 在通知中使用自定义渠道

```php
<?php

namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;

class WelcomeNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database', 'sms']; // 包含自定义渠道
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('欢迎加入我们！')
            ->line('感谢您注册我们的应用。');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => '欢迎加入我们！',
            'message' => '感谢您注册我们的应用。',
        ];
    }

    public function toSms($notifiable)
    {
        return [
            'content' => "你好 {$notifiable->name}，欢迎加入我们！",
            'template' => 'SMS_WELCOME',
            'data' => [
                'name' => $notifiable->name,
                'time' => date('Y-m-d H:i:s'),
            ],
        ];
    }
}
```

### 4. 在 notifiable 中配置路由

```php
<?php

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Apffth\Hyperf\Notification\Notifiable;

class User extends Model
{
    use Notifiable;

    public $phone;

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
```

### 5. 发送通知

```php
$user = User::find(1);
$user->notify(new WelcomeNotification());
```

### 6. 渠道管理器

你也可以直接使用 `ChannelManager` 来管理渠道：

```php
use Apffth\Hyperf\Notification\ChannelManager;

$channelManager = new ChannelManager();

// 注册渠道
$channelManager->register('sms', SmsChannel::class);

// 检查渠道是否存在
if ($channelManager->has('sms')) {
    echo 'SMS 渠道已注册';
}

// 获取所有已注册的渠道
$channels = $channelManager->getRegisteredChannels();
```

### 7. 常用自定义渠道示例

#### Slack 渠道

```php
class SlackChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toSlack($notifiable);
        $channel = $notifiable->routeNotificationFor('slack') ?? '#general';
        
        // 发送到 Slack
        $this->sendToSlack($channel, $message);
    }
}
```

#### 微信渠道

```php
class WeChatChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toWeChat($notifiable);
        $openid = $notifiable->routeNotificationFor('wechat');
        
        // 发送微信消息
        $this->sendWeChatMessage($openid, $message);
    }
}
```

#### 钉钉渠道

```php
class DingTalkChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toDingTalk($notifiable);
        $webhook = $notifiable->routeNotificationFor('dingtalk');
        
        // 发送钉钉消息
        $this->sendDingTalkMessage($webhook, $message);
    }
}
```

## 支持的通知渠道

- **mail**: 邮件通知
- **database**: 数据库通知
- **broadcast**: 广播通知
- **slack**: Slack 通知
- **sms**: 短信通知（自定义）
- **wechat**: 微信通知（自定义）
- **dingtalk**: 钉钉通知（自定义）
- 更多自定义渠道...

## 事件监听

```php
<?php

namespace App\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Apffth\Hyperf\Notification\Events\NotificationSent;

#[Listener]
class NotificationSentListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            NotificationSent::class,
        ];
    }

    public function process(object $event): void
    {
        // 处理通知发送后的事件
        $notification = $event->notification;
        $channel = $event->channel;
        
        // 记录日志等操作
    }
}
```

## 许可证

MIT License 