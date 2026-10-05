<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class BranchModel extends Model
{
    protected $table = 'organization_branches';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
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
}
