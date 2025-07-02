<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Channels;

use Apffth\Hyperf\Notification\Notification;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;

use function Hyperf\Config\config;

class MailChannel implements ChannelInterface
{
    public function send($notifiable, Notification $notification)
    {
        $email = $notification->toMail($notifiable);

        $email->from(new Address(config('mail.from.address'), config('mail.from.name')))
            ->to(new Address($this->getEmail($notifiable)));

        $mailer = $this->getMailer();
        $mailer->send($email);
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
