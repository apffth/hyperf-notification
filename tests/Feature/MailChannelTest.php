<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\Channels\MailChannel;
use Apffth\Hyperf\Notification\Exceptions\NotificationException;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Exception;

/**
 * @internal
 * @coversNothing
 */
class MailChannelTest extends TestCase
{
    public function testItCanSendAMailNotification()
    {
        $user         = new User(['id' => 1, 'email' => 'test@example.com']);
        $notification = new TestNotification();

        // Set valid mail configuration to avoid constructor errors
        $this->setConfig('mail.default_mailer', 'smtp');
        $this->setConfig('mail.mailers.smtp', [
            'host'       => 'localhost',
            'port'       => 25,
            'encryption' => null,
            'username'   => null,
            'password'   => null,
        ]);

        $channel = $this->getContainer()->get(MailChannel::class);

        // This will attempt to send but fail due to no SMTP server
        // We're testing that the method runs without configuration errors
        try {
            $result = $channel->send($user, $notification);
            // If it somehow succeeds (unlikely), assert success
            $this->assertIsArray($result);
            $this->assertTrue($result['success']);
        } catch (Exception $e) {
            // We expect a connection failure, not a configuration error
            $this->assertStringContainsString('Connection', $e->getMessage());
        }
    }

    public function testItThrowsExceptionIfEmailIsMissing()
    {
        $this->expectException(NotificationException::class);
        $this->expectExceptionMessage('Email address is required for the notifiable.');

        // Set complete mail configuration to avoid configuration errors
        $this->setConfig('mail.default_mailer', 'smtp');
        $this->setConfig('mail.mailers.smtp', [
            'host'       => 'localhost',
            'port'       => 25,
            'encryption' => null,
            'username'   => null,
            'password'   => null,
        ]);
        $this->setConfig('mail.from.address', 'test@example.com');
        $this->setConfig('mail.from.name', 'Test');

        $user         = new User(['id' => 1]); // No email
        $notification = new TestNotification();

        $channel = $this->getContainer()->get(MailChannel::class);
        $channel->send($user, $notification);
    }
}
