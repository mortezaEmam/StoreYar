<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PosReturnModel extends Model
{
    protected $table = 'pos_returns';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'pos_sale_id', 'warehouse_id', 'document_number',
        'total_amount', 'currency', 'status', 'reason', 'created_by', 'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'returned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosReturnLineModel::class, 'pos_return_id');
    }
}
