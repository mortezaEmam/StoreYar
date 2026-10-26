<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StockCountModel extends Model
{
    protected $table = 'inventory_stock_counts';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'warehouse_id', 'document_number', 'status',
        'notes', 'created_by', 'counted_at', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'counted_at' => 'datetime',
            'posted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLineModel::class, 'stock_count_id');
    }
}
