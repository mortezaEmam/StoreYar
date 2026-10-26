<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class FiscalYearModel extends Model
{
    protected $table = 'accounting_fiscal_years';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'name', 'starts_on', 'ends_on', 'status', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
