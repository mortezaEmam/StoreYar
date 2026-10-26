<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class SyncEventModel extends Model
{
    protected $table = 'sync_events';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'device_id', 'client_event_id', 'event_type',
        'payload', 'status', 'result_ref', 'error_message',
        'client_occurred_at', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'client_occurred_at' => 'datetime',
            'applied_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
