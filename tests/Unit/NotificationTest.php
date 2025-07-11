<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Unit;

use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class NotificationTest extends TestCase
{
    public function testIdIsSet()
    {
        $notification = new TestNotification();
        $notification->setId();
        $this->assertNotEmpty($notification->getId());
    }

    public function testQueueableMethods()
    {
        $notification = new TestNotification();
        $notification->onQueue('test-queue')
            ->delay(10)
            ->tries(3);

        $this->assertSame('test-queue', $notification->getQueueName());
        $this->assertSame(10, $notification->getDelay());
        $this->assertSame(3, $notification->getTries());
    }
}
