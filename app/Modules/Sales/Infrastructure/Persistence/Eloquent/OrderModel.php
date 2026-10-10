<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class OrderModel extends Model
{
    protected $table = 'sales_orders';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'customer_user_id',
        'status',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLineModel::class, 'order_id', 'id');
    }
}
