<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\Channels\MailChannel;
use Apffth\Hyperf\Notification\Exceptions\NotificationException;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Apffth\Hyperf\Notification\TwigServiceProvider;
use Mockery;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * @property \Hyperf\Di\Container $container
 */
class MailChannelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testItCanSendAMailNotification()
    {
        $user         = new User(['id' => 1, 'email' => 'test@example.com']);
        $notification = new TestNotification();

        $mailer = Mockery::mock(MailerInterface::class);
        $mailer->shouldReceive('send')->once()->with(Mockery::on(function (Email $email) use ($user) {
            return $email->getTo()[0]->getAddress() === $user->email;
        }));
        $this->container->set(MailerInterface::class, $mailer);

        $twig = $this->container->get(TwigServiceProvider::class);

        $channel = new MailChannel($twig, $mailer);
        $channel->send($user, $notification);
    }

    public function testItThrowsExceptionIfEmailIsMissing()
    {
        $this->expectException(NotificationException::class);

        $user         = new User(['id' => 1]); // No email
        $notification = new TestNotification();

        $mailer = Mockery::mock(MailerInterface::class);
        $mailer->shouldReceive('send')->never();
        $this->container->set(MailerInterface::class, $mailer);

        $twig = $this->container->get(TwigServiceProvider::class);

        $channel = new MailChannel($twig, $mailer);
        $channel->send($user, $notification);
    }
} 