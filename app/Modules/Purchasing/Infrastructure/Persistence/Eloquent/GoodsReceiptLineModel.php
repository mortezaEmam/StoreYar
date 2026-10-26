<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class GoodsReceiptLineModel extends Model
{
    protected $table = 'goods_receipt_lines';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'goods_receipt_id', 'purchase_order_line_id', 'product_id',
        'quantity', 'stock_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
