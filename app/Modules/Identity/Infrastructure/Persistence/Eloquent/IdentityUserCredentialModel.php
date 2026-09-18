<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class IdentityUserCredentialModel extends Model
{
    protected $table = 'identity_user_credentials';

    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'password_hash',
    ];

    protected $casts = [
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
