<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\Channels\DatabaseChannel;
use Apffth\Hyperf\Notification\Models\Notification as NotificationModel;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Hyperf\Database\Model\Model;
use Mockery;

class DatabaseChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::unsetConnectionResolver();
    }

    public function testItCanSendADatabaseNotification()
    {
        $user = new User();
        $notification = new TestNotification();

        $channel = $this->getContainer()->get(DatabaseChannel::class);
        $result = $channel->send($user, $notification);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('notification_id', $result);
    }
} 