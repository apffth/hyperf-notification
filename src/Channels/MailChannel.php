<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Channels;

use Apffth\Hyperf\Notification\Exceptions\NotificationException;
use Apffth\Hyperf\Notification\Notification;
use Apffth\Hyperf\Notification\TwigServiceProvider;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

use function Hyperf\Config\config;
use function Hyperf\Coroutine\run;

class MailChannel implements ChannelInterface
{
    public function __construct(protected TwigServiceProvider $twigServiceProvider, protected MailerInterface $mailer) {}

    public function send($notifiable, Notification $notification): mixed
    {
        $email = $notification->toMail($notifiable);

        $toAddress = $this->getEmail($notifiable);
        if (empty($toAddress)) {
            throw new NotificationException(400, 'Email address is required for the notifiable.');
        }

        // 如果是 TemplatedEmail，确保 Twig 环境已配置
        if ($email instanceof TemplatedEmail) {
            $this->configureTemplatedEmail($email);
        }

        $email->from(new Address(config('mail.from.address'), config('mail.from.name')))
            ->to(new Address($toAddress));

        if ($notification->shouldQueue($notifiable)) {
            $this->mailer->send($email);
        } else {
            run(function () use ($email) {
                $this->mailer->send($email);
            });
        }

        return [
            'success' => true,
            'message' => 'Email sent successfully',
        ];
    }

    /**
     * 配置 TemplatedEmail 的 Twig 环境
     * 根据 Symfony Mailer 文档，使用 BodyRenderer 来渲染模板
     */
    protected function configureTemplatedEmail(TemplatedEmail $email): void
    {
        // 使用 Symfony 的 BodyRenderer 来渲染模板
        // 这是 Symfony Mailer 与 Twig 集成的标准方式
        $this->twigServiceProvider->renderTemplatedEmail($email);
    }

    private function getEmail($notifiable): ?string
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable->routeNotificationFor('mail');
        }

        return $notifiable->email ?? null;
    }
}
