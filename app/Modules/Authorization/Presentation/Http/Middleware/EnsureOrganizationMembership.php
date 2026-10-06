<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use StoreYar\Modules\Authorization\Application\Queries\GetMembership\GetMembershipQuery;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class EnsureOrganizationMembership
{
    public function __construct(
        private QueryBus $queries,
        private SessionRepository $sessions,
        private OrganizationRepository $organizations,
    ) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {

        if (count($roles) === 1 && str_contains($roles[0], ',')) {
            $roles = array_map('trim', explode(',', $roles[0]));
        }

        /** @var SessionId|null $sessionId */
        $sessionId = $request->attributes->get('session_id');

        if (! $sessionId instanceof SessionId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $organizationId = $request->route('id')
            ?? $request->header('X-Business-Id');

        if ($organizationId === null || $organizationId === '') {
            return response()->json([
                'message' => 'Organization context is required.',
            ], 400);
        }

        try {
            $orgId = OrganizationId::fromString((string) $organizationId);
        } catch (\InvalidArgumentException) {
            return response()->json(['message' => 'Organization not found.'], 404);
        }

        $organization = $this->organizations->findById($orgId);

        if ($organization === null) {
            return response()->json(['message' => 'Organization not found.'], 404);
        }

        $sessionUserId = $session->userId();

        $userId = is_object($sessionUserId) && method_exists($sessionUserId, 'value')
            ? $sessionUserId->value()
            : (string) $sessionUserId;

        $membership = $this->queries->ask(
            new GetMembershipQuery(
                organizationId: $organization->organizationId()->value(),
                userId: $userId,
            ),
        );

        if ($membership === null) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($roles !== []) {
            $allowed = [];

            foreach ($roles as $role) {
                $allowed[] = Role::from($role);
            }

            if (! in_array($membership->role(), $allowed, true)) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $request->attributes->set('membership', $membership);
        $request->attributes->set('membership_role', $membership->role());

        return $next($request);
    }
}
