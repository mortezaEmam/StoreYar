<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class RolePermissionModel extends Model
{
    protected $table = 'authorization_role_permissions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'role', 'permission_code',
    ];
}
