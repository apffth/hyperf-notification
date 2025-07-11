<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\Feature;

use Apffth\Hyperf\Notification\Channels\MailChannel;
use Apffth\Hyperf\Notification\Exceptions\NotificationException;
use Apffth\Hyperf\Notification\Tests\stubs\TestNotification;
use Apffth\Hyperf\Notification\Tests\stubs\User;
use Apffth\Hyperf\Notification\Tests\TestCase;
use Apffth\Hyperf\Notification\TwigServiceProvider;
use Hyperf\Di\Container;
use Mockery;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

/**
 * @property Container $container
 * @internal
 * @coversNothing
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

        // 模拟邮件配置
        $this->setConfig('mail.default_mailer', 'smtp');
        $this->setConfig('mail.mailers.smtp', [
            'host'       => 'localhost',
            'port'       => 587,
            'encryption' => 'tls',
            'username'   => 'test',
            'password'   => 'test',
        ]);
        $this->setConfig('mail.from.address', 'noreply@example.com');
        $this->setConfig('mail.from.name', 'Test App');

        $twig    = $this->container->get(TwigServiceProvider::class);
        $channel = new MailChannel($twig);

        // 由于 MailChannel 内部创建 Mailer，我们需要模拟网络调用
        // 这里我们主要测试配置和参数传递是否正确
        $result = $channel->send($user, $notification);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Email sent successfully', $result['message']);
    }

    public function testItThrowsExceptionIfEmailIsMissing()
    {
        $this->expectException(NotificationException::class);
        $this->expectExceptionMessage('Email address is required for the notifiable.');

        $user         = new User(['id' => 1]); // No email
        $notification = new TestNotification();

        $twig    = $this->container->get(TwigServiceProvider::class);
        $channel = new MailChannel($twig);
        $channel->send($user, $notification);
    }

    public function testItCanHandleTemplatedEmail()
    {
        $user = new User(['id' => 1, 'email' => 'test@example.com']);

        // 创建一个使用 TemplatedEmail 的通知
        $notification = new class extends TestNotification {
            public function toMail($notifiable): TemplatedEmail
            {
                $email = new TemplatedEmail();
                $email->subject('Test Template Email')
                    ->htmlTemplate('test_template.html.twig')
                    ->context([
                        'userName' => 'Test User',
                    ]);

                return $email;
            }
        };

        // 模拟邮件配置
        $this->setConfig('mail.default_mailer', 'smtp');
        $this->setConfig('mail.mailers.smtp', [
            'host'       => 'localhost',
            'port'       => 587,
            'encryption' => 'tls',
            'username'   => 'test',
            'password'   => 'test',
        ]);
        $this->setConfig('mail.from.address', 'noreply@example.com');
        $this->setConfig('mail.from.name', 'Test App');

        $twig    = $this->container->get(TwigServiceProvider::class);
        $channel = new MailChannel($twig);

        $result = $channel->send($user, $notification);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }
}
