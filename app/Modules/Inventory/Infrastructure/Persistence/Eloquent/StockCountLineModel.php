<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class StockCountLineModel extends Model
{
    protected $table = 'inventory_stock_count_lines';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'stock_count_id', 'product_id', 'system_quantity',
        'counted_quantity', 'variance_quantity', 'adjustment_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'integer',
            'counted_quantity' => 'integer',
            'variance_quantity' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
