<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Unit;

use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Hyperf\Database\Model\Relations\MorphMany;

/**
 * @internal
 * @coversNothing
 */
class NotifiableTest extends TestCase
{
    public function testNotificationsRelation()
    {
        $user = new User();
        $this->assertInstanceOf(MorphMany::class, $user->notifications());
    }

    public function testRouteNotificationFor()
    {
        $user = new User(['email' => 'test@example.com']);
        $this->assertSame('test@example.com', $user->routeNotificationFor('mail'));
    }
}
