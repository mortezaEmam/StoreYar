<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class SyncCursorModel extends Model
{
    protected $table = 'sync_cursors';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id', 'organization_id', 'device_id', 'stream', 'cursor_value', 'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }
}
