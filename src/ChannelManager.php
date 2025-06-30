<?php

namespace Hyperf\Notification;

use Hyperf\Notification\Channels\ChannelInterface;
use Hyperf\Notification\Channels\MailChannel;
use Hyperf\Notification\Channels\DatabaseChannel;
use Hyperf\Notification\Channels\BroadcastChannel;
use Hyperf\Context\ApplicationContext;

class ChannelManager
{
    /**
     * 已注册的渠道
     */
    protected array $channels = [];

    /**
     * 默认渠道映射
     */
    protected array $defaultChannels = [
        'mail' => MailChannel::class,
        'database' => DatabaseChannel::class,
        'broadcast' => BroadcastChannel::class,
    ];

    public function __construct()
    {
        // 注册默认渠道
        foreach ($this->defaultChannels as $name => $class) {
            $this->register($name, $class);
        }
    }

    /**
     * 注册自定义渠道
     */
    public function register(string $name, string $channelClass): self
    {
        if (!is_subclass_of($channelClass, ChannelInterface::class)) {
            throw new \InvalidArgumentException(
                "Channel class {$channelClass} must implement " . ChannelInterface::class
            );
        }

        $this->channels[$name] = $channelClass;
        return $this;
    }

    /**
     * 注册自定义渠道实例
     */
    public function registerInstance(string $name, ChannelInterface $channel): self
    {
        $this->channels[$name] = $channel;
        return $this;
    }

    /**
     * 获取渠道实例
     */
    public function get(string $name): ?ChannelInterface
    {
        if (!isset($this->channels[$name])) {
            return null;
        }

        $channel = $this->channels[$name];

        // 如果是实例，直接返回
        if ($channel instanceof ChannelInterface) {
            return $channel;
        }

        // 如果是类名，尝试从容器获取或实例化
        if (is_string($channel)) {
            if (ApplicationContext::hasContainer()) {
                $container = ApplicationContext::getContainer();
                if ($container->has($channel)) {
                    return $container->get($channel);
                }
            }

            return new $channel();
        }

        return null;
    }

    /**
     * 检查渠道是否存在
     */
    public function has(string $name): bool
    {
        return isset($this->channels[$name]);
    }

    /**
     * 获取所有已注册的渠道名称
     */
    public function getRegisteredChannels(): array
    {
        return array_keys($this->channels);
    }

    /**
     * 移除渠道
     */
    public function remove(string $name): self
    {
        unset($this->channels[$name]);
        return $this;
    }

    /**
     * 清空所有自定义渠道（保留默认渠道）
     */
    public function clear(): self
    {
        $this->channels = $this->defaultChannels;
        return $this;
    }

    /**
     * 获取默认渠道映射
     */
    public function getDefaultChannels(): array
    {
        return $this->defaultChannels;
    }
}
