<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class MembershipModel extends Model
{
    protected $table = 'authorization_memberships';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'user_id',
        'role',
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
