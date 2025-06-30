# Hyperf Notification

一个与 Laravel Notifications 完全兼容的 Hyperf 通知系统。

## 功能特性

- ✅ 支持多种通知渠道（邮件、数据库、广播、Slack等）
- ✅ 队列化通知支持
- ✅ 通知事件系统
- ✅ 数据库通知存储
- ✅ 与 Laravel Notification API 完全兼容
- ✅ 支持通知路由
- ✅ 支持通知标记（已读/未读）
- ✅ 支持 notifiable 别名，保持数据库类型标识一致
- ✅ 支持自定义通知渠道

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

# 邮件配置
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## 使用方法

### 1. 在模型中添加 Notifiable trait

```php
<?php

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\Notification\Notifiable;

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

use Hyperf\Notification\Notification;
use Hyperf\Notification\Messages\MailMessage;

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

### 4. 查询通知

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

### 5. 使用 Notifiable 别名

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
use Hyperf\Notification\Notifiable;

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
use Hyperf\Notification\Notifiable;

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
use Hyperf\Notification\Notifiable;

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

### 6. 队列化通知

如果通知需要队列化处理，实现 `ShouldQueue` 接口：

```php
<?php

namespace App\Notification;

use Hyperf\Notification\Notification;
use Hyperf\Notification\Contracts\ShouldQueue;

class QueueableNotification extends Notification implements ShouldQueue
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('队列化通知')
            ->line('这是一个队列化的通知。');
    }
}
```

## 自定义通知渠道

系统支持注册自定义通知渠道，让你可以轻松扩展通知功能。

### 1. 创建自定义渠道

自定义渠道需要实现 `ChannelInterface` 接口：

```php
<?php

namespace App\Channels;

use Hyperf\Notification\Channels\ChannelInterface;
use Hyperf\Notification\Notification;

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
use Hyperf\Notification\NotificationSender;
use App\Channels\SmsChannel;

// 注册渠道类
NotificationSender::registerChannel('sms', SmsChannel::class);
```

#### 方式二：注册渠道实例

```php
use Hyperf\Notification\NotificationSender;
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

use Hyperf\Notification\Notification;

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
use Hyperf\Notification\Notifiable;

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
use Hyperf\Notification\ChannelManager;

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
use Hyperf\Notification\Events\NotificationSent;

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