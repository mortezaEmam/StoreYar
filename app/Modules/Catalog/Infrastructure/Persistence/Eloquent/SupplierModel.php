<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Temporary placement under Catalog until a dedicated Purchasing/Supplier module is extracted.
 * Table: suppliers
 */
final class SupplierModel extends Model
{
    protected $table = 'suppliers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'code',
        'name',
        'phone',
        'email',
        'tax_id',
        'economic_code',
        'credit_limit_amount',
        'currency',
        'payment_terms_days',
        'is_active',
        'address',
        'bank_info',
        'notes',
        'outstanding_balance_amount',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit_amount' => 'integer',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
            'outstanding_balance_amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
