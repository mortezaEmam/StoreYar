<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PurchaseOrderLineModel extends Model
{
    protected $table = 'purchase_order_lines';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'purchase_order_id', 'product_id', 'quantity_ordered',
        'quantity_received', 'unit_cost_amount', 'line_total_amount', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'integer',
            'quantity_received' => 'integer',
            'unit_cost_amount' => 'integer',
            'line_total_amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
