<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class UnitModel extends Model
{
    protected $table = 'catalog_units';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'code',
        'name',
        'symbol',
        'is_base',
    ];

    protected function casts(): array
    {
        return [
            'is_base' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
