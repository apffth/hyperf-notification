<?php

declare(strict_types=1);

namespace Examples;

use Apffth\Hyperf\Notification\EventDispatcher;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Apffth\Hyperf\Notification\Events\NotificationFailed;
use Apffth\Hyperf\Notification\Notification;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * EventDispatcher 使用示例
 * 展示如何在不同环境下使用修复后的事件分发器
 */
class EventDispatcherUsageExample
{
    /**
     * 示例1: 在开发环境中使用（有完整的日志系统）
     */
    public function developmentEnvironmentExample(): void
    {
        echo "=== 开发环境示例 ===\n";
        
        // 模拟开发环境：容器中有完整的日志系统
        $container = $this->createDevelopmentContainer();
        
        $dispatcher = new EventDispatcher($container, true);
        
        // 添加事件监听器
        $dispatcher->listen('notification.sending', function (NotificationSending $event) {
            echo "📤 准备发送通知: " . get_class($event->getNotification()) . "\n";
        });
        
        $dispatcher->listen('notification.sent', function (NotificationSent $event) {
            echo "✅ 通知发送成功: " . $event->getChannel() . "\n";
        });
        
        $dispatcher->listen('notification.failed', function (NotificationFailed $event) {
            echo "❌ 通知发送失败: " . $event->getErrorMessage() . "\n";
        });
        
        // 测试事件分发
        $this->testEventDispatching($dispatcher);
        
        echo "✓ 开发环境示例完成\n\n";
    }
    
    /**
     * 示例2: 在测试环境中使用（没有日志系统）
     */
    public function testingEnvironmentExample(): void
    {
        echo "=== 测试环境示例 ===\n";
        
        // 模拟测试环境：容器中没有日志系统
        $container = $this->createTestingContainer();
        
        $dispatcher = new EventDispatcher($container, true);
        
        // 验证日志实例为 null，但事件系统仍然工作
        if ($dispatcher->getLogger() === null) {
            echo "ℹ️  测试环境：没有日志系统，但事件分发器正常工作\n";
        }
        
        // 添加事件监听器
        $dispatcher->listen('notification.sending', function (NotificationSending $event) {
            echo "📤 [TEST] 准备发送通知: " . get_class($event->getNotification()) . "\n";
        });
        
        // 测试事件分发
        $this->testEventDispatching($dispatcher);
        
        echo "✓ 测试环境示例完成\n\n";
    }
    
    /**
     * 示例3: 在生产环境中使用（手动配置日志）
     */
    public function productionEnvironmentExample(): void
    {
        echo "=== 生产环境示例 ===\n";
        
        // 模拟生产环境：容器中没有日志系统，但手动配置
        $container = $this->createProductionContainer();
        
        $dispatcher = new EventDispatcher($container, true);
        
        // 手动设置日志实例
        $logger = $this->createProductionLogger();
        $dispatcher->setLogger($logger);
        
        echo "ℹ️  生产环境：手动配置了日志系统\n";
        
        // 添加事件监听器
        $dispatcher->listen('notification.sending', function (NotificationSending $event) {
            echo "📤 [PROD] 准备发送通知: " . get_class($event->getNotification()) . "\n";
        });
        
        // 测试事件分发
        $this->testEventDispatching($dispatcher);
        
        echo "✓ 生产环境示例完成\n\n";
    }
    
    /**
     * 示例4: 禁用事件系统
     */
    public function disabledEventsExample(): void
    {
        echo "=== 禁用事件系统示例 ===\n";
        
        $container = $this->createTestingContainer();
        
        $dispatcher = new EventDispatcher($container, false); // 禁用事件
        
        // 添加事件监听器（不会被执行）
        $dispatcher->listen('notification.sending', function (NotificationSending $event) {
            echo "❌ 这个监听器不应该被执行\n";
        });
        
        // 测试事件分发（应该不会执行任何监听器）
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        
        $sendingEvent = new NotificationSending($notifiable, $notification, 'mail');
        $dispatcher->dispatchSending($sendingEvent);
        
        echo "ℹ️  事件系统已禁用，没有监听器被执行\n";
        echo "✓ 禁用事件系统示例完成\n\n";
    }
    
    /**
     * 示例5: 动态启用/禁用事件系统
     */
    public function dynamicEnableDisableExample(): void
    {
        echo "=== 动态启用/禁用示例 ===\n";
        
        $container = $this->createTestingContainer();
        
        $dispatcher = new EventDispatcher($container, false); // 初始禁用
        
        // 添加事件监听器
        $dispatcher->listen('notification.sending', function (NotificationSending $event) {
            echo "📤 事件监听器执行: " . get_class($event->getNotification()) . "\n";
        });
        
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        $sendingEvent = new NotificationSending($notifiable, $notification, 'mail');
        
        // 测试禁用状态
        echo "1. 禁用状态测试:\n";
        $dispatcher->dispatchSending($sendingEvent);
        
        // 启用事件系统
        echo "2. 启用事件系统:\n";
        $dispatcher->setEnabled(true);
        $dispatcher->dispatchSending($sendingEvent);
        
        // 再次禁用
        echo "3. 再次禁用:\n";
        $dispatcher->setEnabled(false);
        $dispatcher->dispatchSending($sendingEvent);
        
        echo "✓ 动态启用/禁用示例完成\n\n";
    }
    
    /**
     * 测试事件分发
     */
    private function testEventDispatching(EventDispatcher $dispatcher): void
    {
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        
        // 测试发送前事件
        $sendingEvent = new NotificationSending($notifiable, $notification, 'mail');
        $dispatcher->dispatchSending($sendingEvent);
        
        // 测试发送后事件
        $sentEvent = new NotificationSent($notifiable, $notification, 'mail', ['success' => true]);
        $dispatcher->dispatchSent($sentEvent);
        
        // 测试失败事件
        $failedEvent = new NotificationFailed($notifiable, $notification, 'mail', new \Exception('Test error'));
        $dispatcher->dispatchFailed($failedEvent);
    }
    
    /**
     * 创建开发环境容器
     */
    private function createDevelopmentContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get(string $id)
            {
                if ($id === 'Hyperf\Logger\LoggerFactory') {
                    return new class {
                        public function get(string $name): LoggerInterface
                        {
                            return new class implements LoggerInterface {
                                public function emergency($message, array $context = []): void {}
                                public function alert($message, array $context = []): void {}
                                public function critical($message, array $context = []): void {}
                                public function error($message, array $context = []): void {}
                                public function warning($message, array $context = []): void {}
                                public function notice($message, array $context = []): void {}
                                public function info($message, array $context = []): void 
                                {
                                    echo "📝 [LOG] {$message}\n";
                                }
                                public function debug($message, array $context = []): void {}
                                public function log($level, $message, array $context = []): void {}
                            };
                        }
                    };
                }
                throw new \Exception("Service {$id} not found");
            }
            
            public function has(string $id): bool
            {
                return $id === 'Hyperf\Logger\LoggerFactory';
            }
        };
    }
    
    /**
     * 创建测试环境容器
     */
    private function createTestingContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get(string $id)
            {
                throw new \Exception("Service {$id} not found");
            }
            
            public function has(string $id): bool
            {
                return false; // 没有 LoggerFactory
            }
        };
    }
    
    /**
     * 创建生产环境容器
     */
    private function createProductionContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get(string $id)
            {
                throw new \Exception("Service {$id} not found");
            }
            
            public function has(string $id): bool
            {
                return false; // 没有 LoggerFactory
            }
        };
    }
    
    /**
     * 创建生产环境日志器
     */
    private function createProductionLogger(): LoggerInterface
    {
        return new class implements LoggerInterface {
            public function emergency($message, array $context = []): void {}
            public function alert($message, array $context = []): void {}
            public function critical($message, array $context = []): void {}
            public function error($message, array $context = []): void 
            {
                echo "🚨 [PROD ERROR] {$message}\n";
            }
            public function warning($message, array $context = []): void {}
            public function notice($message, array $context = []): void {}
            public function info($message, array $context = []): void 
            {
                echo "ℹ️  [PROD INFO] {$message}\n";
            }
            public function debug($message, array $context = []): void {}
            public function log($level, $message, array $context = []): void {}
        };
    }
    
    /**
     * 运行所有示例
     */
    public function runAllExamples(): void
    {
        echo "🚀 EventDispatcher 使用示例\n";
        echo "展示修复后的事件分发器在不同环境下的使用方法\n\n";
        
        try {
            $this->developmentEnvironmentExample();
            $this->testingEnvironmentExample();
            $this->productionEnvironmentExample();
            $this->disabledEventsExample();
            $this->dynamicEnableDisableExample();
            
            echo "🎉 所有示例运行完成！\n";
            echo "EventDispatcher 现在具有良好的健壮性，可以在各种环境下正常工作。\n";
        } catch (\Throwable $e) {
            echo "❌ 示例运行失败: " . $e->getMessage() . "\n";
            echo "堆栈跟踪: " . $e->getTraceAsString() . "\n";
        }
    }
}

/**
 * 测试用的通知类
 */
class TestNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }
}

/**
 * 测试用的可通知类
 */
class TestNotifiable
{
    public function getKey()
    {
        return 1;
    }
    
    public function routeNotificationFor($channel)
    {
        return 'test@example.com';
    }
}

// 如果直接运行此文件，执行示例
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $example = new EventDispatcherUsageExample();
    $example->runAllExamples();
} 