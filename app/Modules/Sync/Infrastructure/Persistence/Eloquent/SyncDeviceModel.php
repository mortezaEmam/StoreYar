<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class SyncDeviceModel extends Model
{
    protected $table = 'sync_devices';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'device_key', 'name', 'platform',
        'app_version', 'last_seen_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
