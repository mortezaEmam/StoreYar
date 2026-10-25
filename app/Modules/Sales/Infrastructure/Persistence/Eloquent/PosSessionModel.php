<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PosSessionModel extends Model
{
    protected $table = 'pos_sessions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'branch_id', 'warehouse_id', 'register_code',
        'opened_by', 'opened_at', 'opening_float_amount', 'currency',
        'closed_by', 'closed_at', 'closing_count_amount', 'variance_amount',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_float_amount' => 'integer',
            'closing_count_amount' => 'integer',
            'variance_amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
