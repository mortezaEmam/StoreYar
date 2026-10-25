<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PosSaleModel extends Model
{
    protected $table = 'pos_sales';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'pos_session_id', 'warehouse_id', 'customer_id',
        'document_number', 'subtotal_amount', 'discount_amount', 'tax_amount',
        'total_amount', 'currency', 'payment_method', 'status', 'created_by', 'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'sold_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
