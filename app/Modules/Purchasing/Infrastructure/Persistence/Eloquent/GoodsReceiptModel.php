<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GoodsReceiptModel extends Model
{
    protected $table = 'goods_receipts';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'purchase_order_id', 'warehouse_id',
        'document_number', 'status', 'received_at', 'received_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLineModel::class, 'goods_receipt_id');
    }
}
