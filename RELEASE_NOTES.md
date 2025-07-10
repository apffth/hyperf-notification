# Hyperf Notification v1.0.1 发布说明

## 🎉 版本 1.0.1 正式发布

这是一个专为 Hyperf 框架设计的通知系统，兼容 Laravel 通知的 API 设计，提供灵活、可扩展的通知发送功能。

## ✨ 主要特性

### 🚀 高性能
- 基于 Hyperf 框架，深度集成异步队列处理
- 优化的依赖注入架构，提升性能表现

### 📧 多渠道支持
- **邮件 (Mail)**: 使用 Symfony Mailer 发送邮件，支持 Twig 模板
- **数据库 (Database)**: 将通知存储到数据库，支持多态关联
- **自定义渠道**: 支持注册任意自定义通知渠道

### 🔧 易于扩展
- 支持通过依赖注入方便地集成自定义通知渠道
- 模块化设计，每个功能都可以独立使用

### 📝 事件系统
- 与 Hyperf 原生事件系统无缝集成
- 提供完整的通知生命周期事件
- 支持事件监听器的动态注册

### 🎯 Laravel 兼容
- 核心 API 设计与 Laravel 通知保持一致
- 易于从 Laravel 项目迁移

### 🎨 模板支持
- 集成 Twig 模板引擎
- 支持优雅的邮件模板
- 支持 HTML 和纯文本模板

## 📦 安装

```bash
composer require apffth/hyperf-notification
```

## 🔧 配置

发布配置文件：

```bash
php bin/hyperf.php vendor:publish apffth/hyperf-notification
```

运行数据库迁移：

```bash
php bin/hyperf.php migrate
```

## 📖 快速开始

### 1. 创建通知类

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
                'userName' => $notifiable->name,
            ]);

        return $email;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => '一位新用户已注册。',
            'user_id' => $notifiable->getKey(),
        ];
    }
}
```

### 2. 在模型中使用

```php
<?php
namespace App\Model;

use Apffth\Hyperf\Notification\Notifiable;
use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    use Notifiable;
}
```

### 3. 发送通知

```php
$user = User::find(1);
$user->notify(new WelcomeNotification());
```

## 🔄 更新日志

### 新增功能
- 完整的邮件模板支持（Twig）
- 数据库通知存储
- 事件系统集成
- 队列支持
- 自定义渠道注册

### 技术改进
- 依赖注入架构优化
- 测试覆盖完善
- 文档详细化
- 错误处理增强

### 兼容性
- PHP >= 8.1
- Hyperf >= 3.0
- Symfony Mailer >= 7.3

## 🐛 已知问题

暂无已知问题。

## 🔮 未来计划

- [ ] 添加更多内置渠道（短信、推送等）
- [ ] 支持通知模板缓存
- [ ] 添加通知统计功能
- [ ] 支持批量发送优化

## 📄 许可证

本项目采用 MIT 许可证。详见 [LICENSE](LICENSE) 文件。

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📞 支持

如有问题，请通过以下方式联系：

- GitHub Issues: [https://github.com/apffth/hyperf-notification/issues](https://github.com/apffth/hyperf-notification/issues)
- 邮箱: imtoogle@gmail.com

---

感谢使用 Hyperf Notification！🎉 