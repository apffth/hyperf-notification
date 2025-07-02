<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Channels;

use Apffth\Hyperf\Notification\Notification;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Throwable;

use function Hyperf\Config\config;

class MailChannel implements ChannelInterface
{
    public function __construct(private MailerInterface $mailer) {}

    public function send($notifiable, Notification $notification)
    {
        $email = $notification->toMail($notifiable);

        $email->from(new Address(config('mail.from.address'), config('mail.from.name')))
            ->to(new Address($this->getEmail($notifiable)));

        try {
            $this->mailer->send($email);
        } catch (Throwable $th) {
            ApplicationContext::getContainer()
                ->get(LoggerFactory::class)
                ->get('notification', 'default')
                ->error('Failed to send email: ' . $th->__toString());
            throw $th;
        }
    }

    protected function getEmail($notifiable): ?string
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable->routeNotificationFor('mail');
        }

        return $notifiable->email ?? null;
    }
}
