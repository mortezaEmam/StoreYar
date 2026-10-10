<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderLineModel extends Model
{
    protected $table = 'sales_order_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'product_id',
        'quantity',
        'unit_price_amount',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id', 'id');
    }
}
