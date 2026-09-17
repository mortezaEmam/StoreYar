<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class IdentityUserModel extends Model
{
    protected $table = 'identity_users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'name',
        'status',
        'version',
    ];

    protected $casts = [
        'version' => 'integer',
    ];
}
