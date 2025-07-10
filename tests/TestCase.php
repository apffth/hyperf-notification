<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests;

use Apffth\Hyperf\Notification\ChannelManager;
use Apffth\Hyperf\Notification\ConfigProvider;
use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
use Apffth\Hyperf\Notification\EventDispatcher;
use Apffth\Hyperf\Notification\TwigServiceProvider;
use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TestCase extends BaseTestCase
{
    protected ?ContainerInterface $container = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createContainer();
        ApplicationContext::setContainer($this->container);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    protected function createContainer(): ContainerInterface
    {
        $config = new Config([
            'notification' => [
                'queue' => [
                    'queue' => 'test-queue',
                    'delay' => 0,
                    'tries' => 1,
                ],
                'events' => ['enabled' => true],
            ],
            'mail' => [
                'default_mailer' => 'smtp',
                'mailers' => [
                    'smtp' => [
                        'host' => 'localhost',
                        'port' => 25,
                        'encryption' => null,
                        'username' => null,
                        'password' => null,
                    ],
                ],
                'from' => [
                    'address' => 'test@example.com',
                    'name' => 'Test',
                ],
            ],
            'twig' => [
                'paths' => [__DIR__ . '/stubs/views'],
                'options' => ['cache' => false],
            ],
        ]);

        $container = new Container((new DefinitionSourceFactory())());
        $container->set(ConfigInterface::class, $config);
        $container->set(LoggerInterface::class, new NullLogger());
        $container->set(EventDispatcherInterface::class, new EventDispatcher($container, true));
        $container->set(ChannelManager::class, new ChannelManager());
        $container->set(TwigServiceProvider::class, new TwigServiceProvider());

        return $container;
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * 设置配置值用于测试
     */
    protected function setConfig(string $key, mixed $value): void
    {
        $config = $this->container->get(ConfigInterface::class);
        $config->set($key, $value);
    }
} 