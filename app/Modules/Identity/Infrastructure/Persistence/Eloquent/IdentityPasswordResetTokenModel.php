<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class IdentityPasswordResetTokenModel extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'identity_password_reset_tokens';

    protected $primaryKey = 'email';

    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'token_hash',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
