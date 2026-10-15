<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PaymentModel extends Model
{
    protected $table = 'payments_payments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
