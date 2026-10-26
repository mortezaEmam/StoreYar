<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class PermissionModel extends Model
{
    protected $table = 'authorization_permissions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'code', 'name', 'module', 'description',
    ];
}
