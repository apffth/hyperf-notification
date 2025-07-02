<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Channels;

use Apffth\Hyperf\Notification\Notification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

use function Hyperf\Config\config;

class MailChannel implements ChannelInterface
{
    public function __construct(private MailerInterface $mailer) {}

    public function send($notifiable, Notification $notification)
    {
        $email = $notification->toMail($notifiable);

        $email->from(config('mail.from.address'))->to(new Address($this->getEmail($notifiable)));

        $this->mailer->send($email);
    }

    protected function getEmail($notifiable): ?string
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable->routeNotificationFor('mail');
        }

        return $notifiable->email ?? null;
    }
}
