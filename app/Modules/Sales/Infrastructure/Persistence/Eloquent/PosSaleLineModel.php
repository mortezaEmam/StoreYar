<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PosSaleLineModel extends Model
{
    protected $table = 'pos_sale_lines';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'pos_sale_id', 'product_id', 'barcode', 'quantity',
        'unit_price_amount', 'line_total_amount', 'currency', 'stock_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'line_total_amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
