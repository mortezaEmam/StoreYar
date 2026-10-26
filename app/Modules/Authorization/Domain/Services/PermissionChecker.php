<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Domain\Services;

use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent\RolePermissionModel;

final class PermissionChecker
{
    public function allows(Role $role, string $permissionCode): bool
    {
        if ($role->isPrivileged()) {
            return true;
        }

        return RolePermissionModel::query()
            ->where('role', $role->value)
            ->where('permission_code', $permissionCode)
            ->exists();
    }

    /** @return list<string> */
    public function permissionsFor(Role $role): array
    {
        if ($role->isPrivileged()) {
            return ['*'];
        }

        return RolePermissionModel::query()
            ->where('role', $role->value)
            ->orderBy('permission_code')
            ->pluck('permission_code')
            ->all();
    }
}
