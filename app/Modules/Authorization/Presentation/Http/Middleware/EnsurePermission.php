<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\Services\PermissionChecker;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('permission:pos.sale')
 * Requires org.member middleware to have set membership_role on the request.
 */
final class EnsurePermission
{
    public function __construct(
        private PermissionChecker $checker,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $role = $request->attributes->get('membership_role');

        if (! $role instanceof Role) {
            // Fallback if role stored as string
            $raw = $request->attributes->get('membership_role');
            if (is_string($raw)) {
                try {
                    $role = Role::from($raw);
                } catch (\ValueError) {
                    return response()->json(['message' => 'Forbidden.'], 403);
                }
            } else {
                return response()->json(['message' => 'Forbidden. Membership required.'], 403);
            }
        }

        if (! $this->checker->allows($role, $permission)) {
            return response()->json([
                'message' => 'Forbidden.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
