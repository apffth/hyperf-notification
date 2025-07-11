<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\stubs;

use Apffth\Hyperf\Notification\Notification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class TestNotification extends Notification
{
    public function __construct(public string $message = 'Test Message') {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->subject('Test Subject')
            ->htmlTemplate('test_template.html.twig')
            ->context(['message' => $this->message]);
    }

    public function toDatabase($notifiable): array
    {
        return ['message' => $this->message];
    }
}
