# Hyperf Notification

[![Latest Version on Packagist](https://img.shields.io/packagist/v/apffth/hyperf-notification.svg?style=flat-square)](https://packagist.org/packages/apffth/hyperf-notification)
[![Total Downloads](https://img.shields.io/packagist/dt/apffth/hyperf-notification.svg?style=flat-square)](https://packagist.org/packages/apffth/hyperf-notification)
[![License](https://img.shields.io/packagist/l/apffth/hyperf-notification.svg?style=flat-square)](https://github.com/apffth/hyperf-notification/blob/master/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/apffth/hyperf-notification.svg?style=flat-square)](https://packagist.org/packages/apffth/hyperf-notification)
[![Hyperf Version](https://img.shields.io/badge/Hyperf-%3E%3D3.0-blue.svg?style=flat-square)](https://hyperf.io)
[![CI](https://img.shields.io/github/actions/workflow/status/apffth/hyperf-notification/ci.yml?branch=master&style=flat-square)](https://github.com/apffth/hyperf-notification/actions/workflows/ci.yml)
[![Code Style](https://img.shields.io/github/actions/workflow/status/apffth/hyperf-notification/cs.yml?branch=master&style=flat-square)](https://github.com/apffth/hyperf-notification/actions/workflows/cs.yml)
[![GitHub Stars](https://img.shields.io/github/stars/apffth/hyperf-notification.svg?style=flat-square)](https://github.com/apffth/hyperf-notification/stargazers)
[![GitHub Forks](https://img.shields.io/github/forks/apffth/hyperf-notification.svg?style=flat-square)](https://github.com/apffth/hyperf-notification/network)
[![GitHub Issues](https://img.shields.io/github/issues/apffth/hyperf-notification.svg?style=flat-square)](https://github.com/apffth/hyperf-notification/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/apffth/hyperf-notification.svg?style=flat-square)](https://github.com/apffth/hyperf-notification/pulls)

一个专为 Hyperf 框架设计的通知系统，兼容 Laravel 通知的 API 设计，提供灵活、可扩展的通知发送功能。

## 特性

- 🚀 **高性能**: 基于 Hyperf 框架，深度集成异步队列处理。
- 📧 **多渠道支持**: 内置邮件、数据库等核心通知渠道。
- 🔧 **易于扩展**: 支持通过依赖注入方便地集成自定义通知渠道。
- 📝 **事件系统**: 与 Hyperf 原生事件系统无缝集成，提供完整的通知生命周期事件。
- 🎯 **Laravel 兼容**: 核心 API 设计与 Laravel 通知保持一致，易于上手。
- 🎨 **模板支持**: 集成 Twig 模板引擎，支持优雅的邮件模板。

## 支持的渠道

- **邮件 (Mail)**: 使用 Symfony Mailer 发送邮件。
- **数据库 (Database)**: 将通知存储到数据库。
- **自定义渠道**: 支持注册任意自定义通知渠道。

## 环境要求

- PHP >= 8.2
- Hyperf >= 3.0

## 安装

### 1. 通过 Composer 安装

```bash
composer require apffth/hyperf-notification
```

### 2. 发布配置文件和迁移

```bash
php bin/hyperf.php vendor:publish apffth/hyperf-notification
```
该命令会发布 `notification.php`, `mail.php`, `twig.php` 配置文件以及数据库迁移文件。

### 3. 运行数据库迁移

```bash
php bin/hyperf.php migrate
```

## 使用方法

### 1. 创建通知类

使用 `gen:notification` 命令可以快速生成一个通知类。（暂示未支持命令式创建通知类，请手动创建）

```bash
php bin/hyperf.php gen:notification WelcomeNotification
```

通知类定义了通知的发送逻辑和内容。

```php
<?php
// app/Notification/WelcomeNotification.php
namespace App\Notification;

use Apffth\Hyperf\Notification\Notification;
use Apffth\Hyperf\Notification\Queueable;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class WelcomeNotification extends Notification
{
    use Queueable; // 使通知可以被队列化

    /**
     * 定义通知将通过哪些渠道发送
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * 定义通知的邮件内容
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
     * 定义通知的数据库存储内容
     */
    public function toDatabase($notifiable): array
    {
        return [
            'message' => '一位新用户已注册。',
            'user_id' => $notifiable->getKey(), // 使用 getKey() 更安全
        ];
    }

    /**
     * 通知发送完成后的回调方法
     */
    public function afterSend(mixed $response, string $channel, mixed $notifiable): void
    {       
        // 处理邮件渠道的返回值
        if ($channel == 'mail') {
            // 处理邮件发送结果 $response
        }
    }
}
```

### 2. 在模型中使用 Notifiable Trait

在需要接收通知的模型（例如 `User` 模型）中使用 `Notifiable` trait。

```php
<?php
// app/Model/User.php
namespace App\Model;

use Apffth\Hyperf\Notification\Notifiable;
use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    use Notifiable;
    
    // ... 模型其他部分
}
```
`Notifiable` trait 提供了发送通知和管理数据库通知的核心功能。它由 `RoutesNotifications` 和 `HasDatabaseNotifications` 两个更小的 trait 组成，您可以根据需要单独使用它们。

### 3. 发送通知

您可以通过两种方式发送通知：

**a) 使用模型上的 `notify` 方法 (推荐)**

这是最常用、最便捷的方式。

```php
use App\Model\User;
use App\Notification\WelcomeNotification;

$user = User::find(1);
$user->notify(new WelcomeNotification());
```

**b) 使用 `NotificationSender` 服务**

您也可以通过依赖注入直接使用 `NotificationSender` 服务来发送通知。

```php
use Apffth\Hyperf\Notification\NotificationSender;
use App\Model\User;
use App\Notification\WelcomeNotification;

class SomeService
{
    public function __construct(private NotificationSender $sender) {}
    
    public function doSomething()
    {
        $user = User::find(1);
        $this->sender->send($user, new WelcomeNotification());
    }
}
```

### 4. 队列化通知

如果通知类中使用了 `Queueable` trait，通知将自动被推送到队列中异步处理。您可以通过链式调用来动态配置队列属性。

```php
$notification = (new WelcomeNotification())
                    ->onQueue('emails') // 指定队列
                    ->delay(60);        // 延迟60秒

$user->notify($notification);
```

若要同步发送（不使用队列），可以在通知类中重写 `shouldQueue()` 方法使其返回 `false`。

```php
public function shouldQueue($notifiable): bool
{
    return false;
}
```

## 发送前数据准备（`beforeSend`）

### 使用场景

如果您的通知类在构造函数中执行了较重的逻辑（例如数据库查询、外部 RPC 调用、短链生成等），这些逻辑会在 `notify()` 调用的当下（即调用方协程）**同步执行**——即使该通知会被推入队列异步发送，因为 `Notification` 对象是先完整构建好，才被放入队列 Job 的。

`beforeSend()` 钩子专门用来解决这个问题：它**仅在通知即将真正发送前（`via()`/`toXxx()` 之前）调用一次**，无论是同步发送还是异步队列路径皆生效。当通知被队列化时，`beforeSend()` 会被推迟到队列消费协程中执行，调用方（例如同步 gRPC handler）在 `dispatch()` 后可以立即返回，不再被下游服务的网络延迟/超时拖累。

框架会自动保证 `beforeSend()` 幂等（仅执行一次），下游通知类**不需要**自己维护 `resolved`/`prepared` 之类的标志位。

### Before / After 对比范例

**Before**：短链生成逻辑放在构造函数里，`notify()` 调用时同步执行。

```php
class OrderShippedNotification extends Notification
{
    use Queueable;

    private string $shortUrl;

    public function __construct(private Order $order)
    {
        // 每次 new 都会同步触发一次跨服务 RPC 调用，
        // 即使本通知稍后会被推入队列异步发送。
        $this->shortUrl = $this->shortLinkService->generate($order->getTrackingUrl());
    }

    public function via($notifiable): array
    {
        return ['sms'];
    }

    public function toSms($notifiable)
    {
        return "您的包裹已发货，查看物流：{$this->shortUrl}";
    }
}
```

**After**：把短链生成逻辑搬到 `beforeSend()`，仅在真正发送前（队列消费协程中）才执行。

```php
class OrderShippedNotification extends Notification
{
    use Queueable;

    private string $shortUrl;

    public function __construct(private Order $order) {}

    protected function beforeSend(mixed $notifiable): void
    {
        // 仅在即将真正发送前执行一次，若走队列路径，
        // 这里的 RPC 调用发生在队列消费协程，不会阻塞调用方。
        $this->shortUrl = $this->shortLinkService->generate($this->order->getTrackingUrl());
    }

    public function via($notifiable): array
    {
        return ['sms'];
    }

    public function toSms($notifiable)
    {
        return "您的包裹已发货，查看物流：{$this->shortUrl}";
    }
}
```

### 注意事项

- **此钩子整个发送流程仅触发一次，不随渠道数量重复**，这一点与逐渠道触发的 `afterSend()` 语义不同，请勿混淆。
- **`beforeSend()` 抛出异常会导致本次发送的所有渠道皆不执行**（连 `via()` 都不会被调用），这与 `sendNow()` 内部逐渠道 `try/catch`（一个渠道失败不影响其他渠道）的隔离粒度不同：数据都没准备好，各渠道大概率都发不出去，因此设计为整体失败。
  - 队列路径：异常会被 `NotificationJob` 捕获并触发 `failed()`，随后重新抛出交由 `Hyperf\AsyncQueue` 按 `tries()`/`delay()` 设定重试；由于异常发生在幂等标志被置位之前，下次重试会重新执行 `beforeSend()`。
  - 同步路径：异常直接冒泡给调用 `notify()` 的调用方，与现有 `via()`/`toXxx()` 抛异常时的行为一致。

## 事件系统

本组件与 Hyperf 原生的事件系统完全集成。您可以创建标准的事件监听器来监听通知的生命周期事件。

支持的事件包括：
- `Apffth\Hyperf\Notification\Events\NotificationSending` (发送前)
- `Apffth\Hyperf\Notification\Events\NotificationSent` (发送后)
- `Apffth\Hyperf\Notification\Events\NotificationFailed` (发送失败)

### 创建事件监听器

使用 `gen:listener` 命令创建一个监听器。

```bash
php bin/hyperf.php gen:listener LogNotificationStatus
```

### 编写监听器逻辑

在监听器中，使用 `#[Listener]` 注解，并在 `listen()` 方法中返回您想监听的事件类。

```php
<?php
// app/Listener/LogNotificationStatus.php
namespace App\Listener;

use Apffth\Hyperf\Notification\Events\NotificationSent;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;

#[Listener]
class LogNotificationStatus implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(\Hyperf\Logger\LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('notification');
    }

    public function listen(): array
    {
        return [
            NotificationSent::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof NotificationSent) {
            $this->logger->info(sprintf(
                'Notification sent to %s via %s.',
                get_class($event->getNotifiable()),
                $event->getChannel()
            ));
        }
    }
}
```
Hyperf 会自动发现并注册这个监听器。

## 自定义渠道

### 1. 创建渠道类

您的自定义渠道类需要实现 `Apffth\Hyperf\Notification\Channels\ChannelInterface` 接口。

```php
<?php
namespace App\Channels;

use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Notification;

class SmsChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification): mixed
    {
        $message = $notification->toSms($notifiable); // 您需要在通知类中添加 toSms 方法
        // ... 实现发送短信的逻辑
        return ['success' => true];
    }
}
```

### 2. 注册自定义渠道

推荐在 app/Bootstrap 目录下创建 NotificationBootstrap 监听服务启动并注册您的渠道。

```php
<?php

declare(strict_types=1);

namespace App\Bootstrap;

use Apffth\Hyperf\Notification\ChannelManager;
use App\Notification\Channels\SmsChannel;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

#[Listener]
class NotificationBootstrap implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container) {}

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        $channelManager = $this->container->get(ChannelManager::class);
        $channelManager->register('sms', SmsChannel::class);
    }
}
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

使用 `HasDatabaseNotifications` trait (已包含在 `Notifiable` 中) 会为您的模型提供便捷的数据库通知管理方法。

```php
$user = User::find(1);

// 获取用户的所有通知
$notifications = $user->notifications;

// 获取未读通知
$unreadNotifications = $user->unreadNotifications;

// 标记所有通知为已读
$user->markNotificationsAsRead();
```

## 测试

在测试时，您可以通过依赖注入来模拟 `Apffth\Hyperf\Notification\NotificationSender` 或具体的渠道类，以防止发送真实的通知。

```php
// 在您的测试用例中
use Apffth\Hyperf\Notification\NotificationSender;
use Mockery;

// ...
$senderMock = Mockery::mock(NotificationSender::class);
$senderMock->shouldReceive('send')->once();

$this->container->set(NotificationSender::class, $senderMock);

// 执行您的业务逻辑...
```

## 快速安装

[![Composer](https://img.shields.io/badge/Composer-Install-blue.svg?style=for-the-badge&logo=composer)](https://packagist.org/packages/apffth/hyperf-notification)

```bash
composer require apffth/hyperf-notification
```

## 许可证

本项目采用 MIT 许可证。详见 [LICENSE](LICENSE) 文件。 