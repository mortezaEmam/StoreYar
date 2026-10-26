<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PurchaseReturnLineModel extends Model
{
    protected $table = 'purchase_return_lines';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'purchase_return_id', 'product_id', 'quantity',
        'unit_cost_amount', 'line_total_amount', 'stock_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost_amount' => 'integer',
            'line_total_amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
