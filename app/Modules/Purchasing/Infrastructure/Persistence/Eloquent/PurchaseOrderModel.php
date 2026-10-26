<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseOrderModel extends Model
{
    protected $table = 'purchase_orders';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'supplier_id', 'warehouse_id', 'branch_id',
        'document_number', 'status', 'currency', 'subtotal_amount', 'total_amount',
        'notes', 'ordered_at', 'expected_at', 'created_by', 'version',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'integer',
            'total_amount' => 'integer',
            'ordered_at' => 'datetime',
            'expected_at' => 'datetime',
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLineModel::class, 'purchase_order_id');
    }
}
