<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\stubs;

use Apffth\Hyperf\Notification\Notifiable;
use Hyperf\Database\Model\Model;

class User extends Model
{
    use Notifiable;

    protected $fillable = ['id', 'email'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->id    = $attributes['id']    ?? 1;
        $this->email = $attributes['email'] ?? 'test@example.com';
    }

    public function getKey()
    {
        return $this->id;
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
