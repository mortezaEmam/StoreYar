<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Temporary placement under Catalog until a dedicated Customer module is extracted.
 * Table: customers
 */
final class CustomerModel extends Model
{
    protected $table = 'customers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'code',
        'name',
        'phone',
        'email',
        'national_id',
        'customer_type',
        'credit_limit_amount',
        'currency',
        'payment_terms_days',
        'price_level',
        'is_active',
        'address',
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
