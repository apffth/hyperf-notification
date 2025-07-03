<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Channels;

use Apffth\Hyperf\Notification\Notification;
use Apffth\Hyperf\Notification\TwigServiceProvider;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;

use function Hyperf\Config\config;

class MailChannel implements ChannelInterface
{
    public function __construct(protected TwigServiceProvider $twigServiceProvider) {}

    public function send($notifiable, Notification $notification): mixed
    {
        $email = $notification->toMail($notifiable);

        // 如果是 TemplatedEmail，确保 Twig 环境已配置
        if ($email instanceof TemplatedEmail) {
            $this->configureTemplatedEmail($email);
        }

        $email->from(new Address(config('mail.from.address'), config('mail.from.name')))
            ->to(new Address($this->getEmail($notifiable)));

        $mailer = $this->getMailer();
        $result = $mailer->send($email);

        // 返回发送结果信息
        return [
            'success'    => true,
            'message_id' => $email->getHeaders()->get('Message-ID')?->getBodyAsString(),
            'to'         => $this->getEmail($notifiable),
            'subject'    => $email->getSubject(),
            'sent_at'    => date('Y-m-d H:i:s'),
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

    private function getMailer(): Mailer
    {
        $defaultMailer = config('mail.default_mailer');
        $mailer        = config('mail.mailers.' . $defaultMailer);

        $transport = new EsmtpTransport(
            host: $mailer['host'],
            port: $mailer['port'],
            tls: $mailer['encryption'] === 'tls'
        );

        if (! empty($mailer['username'])) {
            $transport->setUsername($mailer['username']);
        }

        if (! empty($mailer['password'])) {
            $transport->setPassword($mailer['password']);
        }

        return new Mailer($transport);
    }
}
