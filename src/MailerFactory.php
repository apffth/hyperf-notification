<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Exceptions\NotificationException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

use function Hyperf\Config\config;

class MailerFactory
{
    public function __invoke(ContainerInterface $container): MailerInterface
    {
        return $this->getMailer();
    }

    private function getMailer(): Mailer
    {
        $defaultMailer = config('mail.default_mailer');
        $mailer        = config('mail.mailers.' . $defaultMailer);

        if (empty($mailer)) {
            throw new NotificationException(500, 'Mailer configuration not found');
        }

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

    // protected function getDsnString(array $config): string
    // {
    //     $defaultMailer = $config['default_mailer'] ?? 'smtp';
    //     $mailerConfig = $config['mailers'][$defaultMailer] ?? null;

    //     if (! $mailerConfig) {
    //         throw new NotificationException(500, 'Default mailer configuration not found.');
    //     }

    //     $host = $mailerConfig['host'];
    //     $port = $mailerConfig['port'];
    //     $user = $mailerConfig['username'] ?? null;
    //     $password = $mailerConfig['password'] ?? null;
    //     $scheme = $mailerConfig['transport'] ?? 'smtp';

    //     $dsn = "{$scheme}://";
    //     if ($user) {
    //         $dsn .= urlencode($user);
    //         if ($password) {
    //             $dsn .= ':' . urlencode($password);
    //         }
    //         $dsn .= '@';
    //     }
    //     $dsn .= "{$host}:{$port}";

    //     return $dsn;
    // }
}
