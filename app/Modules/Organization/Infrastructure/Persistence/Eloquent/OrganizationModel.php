<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class OrganizationModel extends Model
{
    protected $table = 'organization_organizations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'owner_user_id',
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
