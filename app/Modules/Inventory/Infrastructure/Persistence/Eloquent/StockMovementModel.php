<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class StockMovementModel extends Model
{
    protected $table = 'inventory_stock_movements';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'organization_id',
        'warehouse_id',
        'location_id',
        'stock_item_id',
        'product_id',
        'variant_id',
        'movement_type',
        'document_type',
        'document_id',
        'document_line_id',
        'direction',
        'quantity_delta',
        'reserved_delta',
        'quantity_after',
        'reserved_after',
        'unit_id',
        'base_quantity',
        'unit_cost_amount',
        'currency',
        'reason',
        'created_by',
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
            'base_quantity' => 'decimal:4',
            'unit_cost_amount' => 'integer',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
