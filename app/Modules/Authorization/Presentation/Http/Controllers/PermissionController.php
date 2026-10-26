<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\Services\PermissionChecker;
use StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent\PermissionModel;
use StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent\RolePermissionModel;
use StoreYar\Shared\Application\Context\BusinessContext;

final class PermissionController
{
    public function __construct(
        private PermissionChecker $checker,
        private BusinessContext $business,
    ) {}

    public function catalog(): JsonResponse
    {
        $items = PermissionModel::query()->orderBy('module')->orderBy('code')->get();

        return response()->json([
            'data' => $items->map(static fn (PermissionModel $p) => [
                'code' => $p->code,
                'name' => $p->name,
                'module' => $p->module,
                'description' => $p->description,
            ]),
        ]);
    }

    public function myPermissions(Request $request): JsonResponse
    {
        $role = $request->attributes->get('membership_role');
        if (! $role instanceof Role) {
            $raw = $request->attributes->get('membership_role');
            $role = is_string($raw) ? Role::from($raw) : null;
        }
        if ($role === null) {
            return response()->json(['message' => 'No membership role'], 403);
        }

        return response()->json([
            'data' => [
                'role' => $role->value,
                'permissions' => $this->checker->permissionsFor($role),
            ],
        ]);
    }

    public function roleMatrix(): JsonResponse
    {
        $roles = array_map(static fn (Role $r) => $r->value, Role::cases());
        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role] = RolePermissionModel::query()
                ->where('role', $role)
                ->orderBy('permission_code')
                ->pluck('permission_code')
                ->all();
        }

        return response()->json(['data' => $matrix]);
    }
}
