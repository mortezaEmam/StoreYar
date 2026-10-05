<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Domain\Enums;

enum Role: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }

    public function canManageMembers(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    public function canManageOrganization(): bool
    {
        return $this === self::OWNER;
    }
}
