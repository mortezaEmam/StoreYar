<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class OutboxModel extends Model
{
    protected $table = 'shared_outbox';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload',
        'occurred_at',
        'created_at',
        'processed_at',
        'attempts',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'processed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
