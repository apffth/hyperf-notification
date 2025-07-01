<?php

namespace Apffth\Hyperf\Notification\Models;

use Hyperf\DbConnection\Model\Model;
use Hyperf\Database\Model\Relations\MorphTo;

class Notification extends Model
{
    protected ?string $table = 'notifications';

    protected array $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected array $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * 获取通知所属的可通知实体。
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 标记通知为已读。
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * 标记通知为未读。
     */
    public function markAsUnread(): void
    {
        $this->forceFill(['read_at' => null])->save();
    }

    /**
     * 判断通知是否已读。
     */
    public function read(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * 判断通知是否未读。
     */
    public function unread(): bool
    {
        return $this->read_at === null;
    }
}
