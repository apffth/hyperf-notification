<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Tests\stubs;

use Apffth\Hyperf\Notification\Notifiable;
use Hyperf\Database\Model\Model;

class User extends Model
{
    use Notifiable;

    protected array $fillable = ['id', 'email'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->id    = $attributes['id'] ?? 1;
        $this->email = $attributes['email'] ?? null;
    }

    public function getKey()
    {
        return $this->id;
    }

    public function routeNotificationFor($channel): ?string
    {
        if ($channel === 'mail') {
            return $this->email;
        }
        
        return null;
    }
}
