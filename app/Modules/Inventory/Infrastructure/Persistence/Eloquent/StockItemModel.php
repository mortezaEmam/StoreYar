<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class StockItemModel extends Model
{
    protected $table = 'inventory_stock_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'product_id',
        'quantity',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
