<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Exceptions;

use Hyperf\Server\Exception\ServerException;
use Throwable;

class NotificationException extends ServerException
{
    public function __construct(int $code = 0, ?string $message = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
