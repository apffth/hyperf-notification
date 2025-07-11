<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\Channels\DatabaseChannel;
use Apffth\Hyperf\Notification\Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class DatabaseChannelTest extends TestCase
{
    public function testItCanInstantiateDatabaseChannel()
    {
        $channel = $this->getContainer()->get(DatabaseChannel::class);

        $this->assertInstanceOf(DatabaseChannel::class, $channel);
    }

    public function testItHasSendMethod()
    {
        $channel = $this->getContainer()->get(DatabaseChannel::class);

        $this->assertTrue(method_exists($channel, 'send'));
    }
}
