<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class StockMovementModel extends Model
{
    protected $table = 'inventory_stock_movements';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'stock_item_id',
        'product_id',
        'movement_type',
        'quantity_delta',
        'reserved_delta',
        'quantity_after',
        'reserved_after',
        'event_id',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'integer',
            'reserved_delta' => 'integer',
            'quantity_after' => 'integer',
            'reserved_after' => 'integer',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
