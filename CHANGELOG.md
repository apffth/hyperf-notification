# 更新日志

## [1.0.1] - 2025-07-10

### 更改
- **MailChannel 架构调整**: 将 `MailChannel` 改为依赖注入 `TwigServiceProvider`，移除内部 `Mailer` 创建逻辑
- **ConfigProvider 更新**: 添加了 `TwigServiceProvider` 和 `MailChannel` 的依赖注入注册
- **测试用例更新**: 更新了 `MailChannelTest` 以适应新的构造函数和依赖注入架构
- **TestCase 基类增强**: 添加了 `setConfig` 方法以支持测试中的配置设置
- **示例文件更新**: 更新了 `EventUsageExample.php` 以使用新的依赖注入架构
- **PHP 版本要求调整**: 将最低 PHP 版本从 8.2 调整为 8.1

### 技术细节

#### MailChannel 更改
- 构造函数现在接受 `TwigServiceProvider` 依赖注入
- 移除了内部 `Mailer` 创建逻辑，改为从配置中读取
- 添加了对 `TemplatedEmail` 的专门处理
- 改进了错误处理，当收件人地址缺失时抛出异常

#### 依赖注入配置
- `ConfigProvider` 现在注册 `TwigServiceProvider` 和 `MailChannel`
- 支持通过容器获取渠道实例
- 保持了向后兼容性

#### 测试改进
- `MailChannelTest` 现在使用配置模拟而不是直接模拟 `Mailer`
- 添加了对 `TemplatedEmail` 的测试
- `TestCase` 基类提供了 `setConfig` 方法

#### 示例更新
- `EventUsageExample` 现在使用依赖注入而不是静态方法
- 移除了对 `NotificationSender::listen` 静态方法的依赖
- 改为使用 `EventDispatcher` 实例

### 兼容性
- 保持了与现有代码的向后兼容性
- 现有的通知类无需修改
- 配置文件和迁移文件保持不变

### 性能改进
- 减少了 `MailChannel` 的实例化开销
- 改进了依赖注入的性能
- 优化了测试执行速度

### 文档更新
- 更新了 `README.md` 以反映新的架构
- 添加了 `TemplatedEmail` 的使用示例
- 更新了自定义渠道注册的说明 