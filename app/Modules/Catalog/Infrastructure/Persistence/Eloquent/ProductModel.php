<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class ProductModel extends Model
{
    protected $table = 'catalog_products';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'sku',
        'unit_price_amount',
        'currency',
        'status',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_amount' => 'integer',
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
