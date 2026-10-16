<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class ShipmentModel extends Model
{
    protected $table = 'shipping_shipments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'order_id',
        'status',
        'tracking_number',
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
}
