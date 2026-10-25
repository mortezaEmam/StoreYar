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
        'category_id',
        'brand_id',
        'product_type',
        'name',
        'short_name',
        'description',
        'sku',
        'unit_price_amount',
        'cost_price_amount',
        'currency',
        'status',
        'is_sellable',
        'is_purchasable',
        'is_stockable',
        'is_taxable',
        'base_unit_id',
        'sale_unit_id',
        'purchase_unit_id',
        'min_stock',
        'max_stock',
        'reorder_point',
        'reorder_qty',
        'track_lot',
        'track_serial',
        'track_expiry',
        'archived_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_amount' => 'integer',
            'cost_price_amount' => 'integer',
            'is_sellable' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_stockable' => 'boolean',
            'is_taxable' => 'boolean',
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
            'reorder_point' => 'decimal:4',
            'reorder_qty' => 'decimal:4',
            'track_lot' => 'boolean',
            'track_serial' => 'boolean',
            'track_expiry' => 'boolean',
            'version' => 'integer',
            'archived_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
