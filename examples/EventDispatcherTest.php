<?php

declare(strict_types=1);

namespace Examples;

use Apffth\Hyperf\Notification\EventDispatcher;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Apffth\Hyperf\Notification\Events\NotificationFailed;
use Apffth\Hyperf\Notification\Notification;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * EventDispatcher 健壮性测试示例
 */
class EventDispatcherTest
{
    /**
     * 测试没有 LoggerFactory 的情况
     */
    public function testWithoutLoggerFactory(): void
    {
        echo "=== 测试没有 LoggerFactory 的情况 ===\n";
        
        // 创建一个模拟容器，不包含 LoggerFactory
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->with('Hyperf\Logger\LoggerFactory')
            ->andReturn(false);
        
        // 创建 EventDispatcher 实例
        $dispatcher = new EventDispatcher($container, true);
        
        // 验证事件分发器仍然可以正常工作
        $this->assertTrue($dispatcher->isEnabled());
        $this->assertNull($dispatcher->getLogger());
        
        // 测试事件分发（应该不会抛出异常）
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        
        $sendingEvent = new NotificationSending($notifiable, $notification, 'mail');
        $dispatcher->dispatchSending($sendingEvent);
        
        echo "✓ 没有 LoggerFactory 时事件分发器正常工作\n";
    }
    
    /**
     * 测试 LoggerFactory 获取失败的情况
     */
    public function testLoggerFactoryFailure(): void
    {
        echo "=== 测试 LoggerFactory 获取失败的情况 ===\n";
        
        // 创建一个模拟容器，has 返回 true 但 get 抛出异常
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->with('Hyperf\Logger\LoggerFactory')
            ->andReturn(true);
        $container->shouldReceive('get')
            ->with('Hyperf\Logger\LoggerFactory')
            ->andThrow(new \Exception('LoggerFactory not available'));
        
        // 创建 EventDispatcher 实例
        $dispatcher = new EventDispatcher($container, true);
        
        // 验证事件分发器仍然可以正常工作
        $this->assertTrue($dispatcher->isEnabled());
        $this->assertNull($dispatcher->getLogger());
        
        echo "✓ LoggerFactory 获取失败时事件分发器正常工作\n";
    }
    
    /**
     * 测试正常 LoggerFactory 的情况
     */
    public function testWithLoggerFactory(): void
    {
        echo "=== 测试正常 LoggerFactory 的情况 ===\n";
        
        // 创建模拟的 Logger
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->andReturn(true);
        $logger->shouldReceive('error')->andReturn(true);
        
        // 创建模拟的 LoggerFactory
        $loggerFactory = Mockery::mock('Hyperf\Logger\LoggerFactory');
        $loggerFactory->shouldReceive('get')
            ->with('notification')
            ->andReturn($logger);
        
        // 创建模拟容器
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->with('Hyperf\Logger\LoggerFactory')
            ->andReturn(true);
        $container->shouldReceive('get')
            ->with('Hyperf\Logger\LoggerFactory')
            ->andReturn($loggerFactory);
        
        // 创建 EventDispatcher 实例
        $dispatcher = new EventDispatcher($container, true);
        
        // 验证日志实例已正确设置
        $this->assertTrue($dispatcher->isEnabled());
        $this->assertNotNull($dispatcher->getLogger());
        
        // 测试事件分发
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        
        $sendingEvent = new NotificationSending($notifiable, $notification, 'mail');
        $dispatcher->dispatchSending($sendingEvent);
        
        echo "✓ 正常 LoggerFactory 时事件分发器正常工作\n";
    }
    
    /**
     * 测试日志记录失败的情况
     */
    public function testLoggerFailure(): void
    {
        echo "=== 测试日志记录失败的情况 ===\n";
        
        // 创建模拟的 Logger，让它抛出异常
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')
            ->andThrow(new \Exception('Logger write failed'));
        $logger->shouldReceive('error')
            ->andThrow(new \Exception('Logger write failed'));
        
        // 创建模拟的 LoggerFactory
        $loggerFactory = Mockery::mock('Hyperf\Logger\LoggerFactory');
        $loggerFactory->shouldReceive('get')
            ->with('notification')
            ->andReturn($logger);
        
        // 创建模拟容器
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->with('Hyperf\Logger\LoggerFactory')
            ->andReturn(true);
        $container->shouldReceive('get')
            ->with('Hyperf\Logger\LoggerFactory')
            ->andReturn($loggerFactory);
        
        // 创建 EventDispatcher 实例
        $dispatcher = new EventDispatcher($container, true);
        
        // 测试事件分发（应该不会抛出异常，即使日志记录失败）
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        
        $sendingEvent = new NotificationSending($notifiable, $notification, 'mail');
        $dispatcher->dispatchSending($sendingEvent);
        
        echo "✓ 日志记录失败时事件分发器正常工作\n";
    }
    
    /**
     * 测试手动设置日志实例
     */
    public function testManualLoggerSetting(): void
    {
        echo "=== 测试手动设置日志实例 ===\n";
        
        // 创建模拟容器，不包含 LoggerFactory
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->with('Hyperf\Logger\LoggerFactory')
            ->andReturn(false);
        
        // 创建 EventDispatcher 实例
        $dispatcher = new EventDispatcher($container, true);
        
        // 手动设置日志实例
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->andReturn(true);
        
        $dispatcher->setLogger($logger);
        
        // 验证日志实例已正确设置
        $this->assertNotNull($dispatcher->getLogger());
        
        // 测试事件分发
        $notification = new TestNotification();
        $notifiable = new TestNotifiable();
        
        $sendingEvent = new NotificationSending($notifiable, $notification, 'mail');
        $dispatcher->dispatchSending($sendingEvent);
        
        echo "✓ 手动设置日志实例正常工作\n";
    }
    
    /**
     * 运行所有测试
     */
    public function runAllTests(): void
    {
        echo "开始 EventDispatcher 健壮性测试...\n\n";
        
        try {
            $this->testWithoutLoggerFactory();
            $this->testLoggerFactoryFailure();
            $this->testWithLoggerFactory();
            $this->testLoggerFailure();
            $this->testManualLoggerSetting();
            
            echo "\n🎉 所有测试通过！EventDispatcher 具有良好的健壮性。\n";
        } catch (Throwable $e) {
            echo "\n❌ 测试失败: " . $e->getMessage() . "\n";
            echo "堆栈跟踪: " . $e->getTraceAsString() . "\n";
        } finally {
            Mockery::close();
        }
    }
    
    /**
     * 简单的断言方法
     */
    private function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new \Exception($message ?: 'Assertion failed: expected true');
        }
    }
    
    private function assertNull($value, string $message = ''): void
    {
        if ($value !== null) {
            throw new \Exception($message ?: 'Assertion failed: expected null');
        }
    }
    
    private function assertNotNull($value, string $message = ''): void
    {
        if ($value === null) {
            throw new \Exception($message ?: 'Assertion failed: expected not null');
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

// 如果直接运行此文件，执行测试
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new EventDispatcherTest();
    $test->runAllTests();
} 