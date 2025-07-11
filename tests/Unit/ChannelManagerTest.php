<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Unit;

use Apffth\Hyperf\Notification\ChannelManager;
use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Channels\DatabaseChannel;
use Apffth\Hyperf\Notification\Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ChannelManagerTest extends TestCase
{
    public function testDefaultChannelsAreRegistered()
    {
        $manager = $this->getContainer()->get(ChannelManager::class);
        $this->assertInstanceOf(ChannelInterface::class, $manager->get('mail'));
        $this->assertInstanceOf(DatabaseChannel::class, $manager->get('database'));
    }

    public function testRegisterCustomChannel()
    {
        $manager = $this->getContainer()->get(ChannelManager::class);

        $mockChannel = $this->createMock(ChannelInterface::class);
        $manager->registerInstance('custom', $mockChannel);

        $this->assertSame($mockChannel, $manager->get('custom'));
    }
}
