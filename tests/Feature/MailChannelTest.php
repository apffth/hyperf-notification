<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\Channels\MailChannel;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Mockery;

/**
 * @property \Hyperf\Di\Container $container
 */
class MailChannelTest extends TestCase
{
    public function testItCanSendAMailNotification()
    {
        $user = new User();
        $notification = new TestNotification();

        $mailer = Mockery::mock(MailerInterface::class);
        $mailer->shouldReceive('send')->once();
        $this->container->set(MailerInterface::class, $mailer);

        $channel = $this->getContainer()->get(MailChannel::class);
        $result = $channel->send($user, $notification);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }
} 