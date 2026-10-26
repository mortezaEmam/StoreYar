<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseReturnModel extends Model
{
    protected $table = 'purchase_returns';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'purchase_order_id', 'supplier_id', 'warehouse_id',
        'document_number', 'status', 'total_amount', 'currency', 'reason',
        'created_by', 'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'returned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLineModel::class, 'purchase_return_id');
    }
}
