<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests;

use Apffth\Hyperf\Notification\ChannelManager;
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
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

/**
 * @internal
 * @coversNothing
 */
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
            'databases' => [
                'default' => [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'port' => 3306,
                    'database' => 'test',
                    'username' => 'test',
                    'password' => 'test',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'pool' => [
                        'min_connections' => 1,
                        'max_connections' => 10,
                        'connect_timeout' => 10.0,
                        'wait_timeout' => 3.0,
                        'heartbeat' => -1,
                        'max_idle_time' => 60.0,
                    ],
                ],
            ],
            'logger' => [
                'default' => [
                    'handler' => [
                        'class' => \Monolog\Handler\StreamHandler::class,
                        'constructor' => [
                            'stream' => 'php://stdout',
                            'level' => \Monolog\Level::Debug,
                        ],
                    ],
                    'formatter' => [
                        'class' => \Monolog\Formatter\LineFormatter::class,
                        'constructor' => [],
                    ],
                ],
            ],
            'mail' => [
                'default_mailer' => 'array',
                'mailers' => [
                    'array' => [
                        'transport' => 'array',
                    ],
                    'smtp' => [
                        'transport'  => 'smtp',
                        'host'       => 'localhost',
                        'port'       => 25,
                        'encryption' => null,
                        'username'   => null,
                        'password'   => null,
                    ],
                ],
                'from' => [
                    'address' => 'test@example.com',
                    'name'    => 'Test',
                ],
            ],
            'twig' => [
                'paths'   => [__DIR__ . '/stubs/views'],
                'options' => ['cache' => false],
            ],
        ]);

        $container = new Container((new DefinitionSourceFactory())());
        $container->set(ConfigInterface::class, $config);
        $container->set(LoggerInterface::class, new NullLogger());
        $container->set(EventDispatcherInterface::class, new EventDispatcher($container, true));
        $container->set(ChannelManager::class, new ChannelManager());
        $container->set(TwigServiceProvider::class, new TwigServiceProvider());
        $container->set(MailerInterface::class, new Mailer(Transport::fromDsn('null://null')));

        return $container;
    }

    /**
     * @return Container
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * 设置配置值用于测试.
     */
    protected function setConfig(string $key, mixed $value): void
    {
        $config = $this->container->get(ConfigInterface::class);
        $config->set($key, $value);
    }
}
