<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\Channels\DatabaseChannel;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Hyperf\DbConnection\Db;

/**
 * @internal
 * @coversNothing
 */
class DatabaseChannelTest extends TestCase
{
    protected function tearDown(): void
    {
        Db::table('notifications')->truncate();
        parent::tearDown();
    }

    public function testItCanSendADatabaseNotification()
    {
        $user         = new User(['id' => 1, 'email' => 'test@example.com']);
        $notification = new TestNotification();

        $channel = $this->getContainer()->get(DatabaseChannel::class);
        $channel->send($user, $notification);

        $exists = Db::table('notifications')->where([
            'notifiable_id'   => $user->getKey(),
            'notifiable_type' => $user->getMorphClass(),
            'type'            => get_class($notification),
        ])->exists();

        $this->assertTrue($exists);
    }
}
