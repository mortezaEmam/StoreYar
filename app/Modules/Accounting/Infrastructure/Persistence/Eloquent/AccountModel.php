<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class AccountModel extends Model
{
    protected $table = 'accounting_accounts';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'code', 'name', 'type', 'subtype',
        'parent_id', 'is_system', 'is_active', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
