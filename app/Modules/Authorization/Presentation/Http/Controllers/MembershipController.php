<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipCommand;
use StoreYar\Modules\Authorization\Application\Queries\ListMembersByOrganization\ListMembersByOrganizationQuery;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\Exceptions\MembershipAlreadyExists;
use StoreYar\Modules\Authorization\Presentation\Http\Requests\GrantMembershipRequest;
use StoreYar\Modules\Authorization\Presentation\Http\Resources\MembershipResource;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;

final class MembershipController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private UserRepository $users,
    ) {}

    public function index(Request $request, string $id): JsonResponse
    {
        $members = $this->queries->ask(
            new ListMembersByOrganizationQuery(organizationId: $id),
        );

        return MembershipResource::collection($members)
            ->response()
            ->setStatusCode(200);
    }

    public function store(GrantMembershipRequest $request, string $id): JsonResponse
    {
        $userId = $request->string('user_id')->toString();

        $user = $this->users->findById(UserId::fromString($userId));

        if ($user === null) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        try {
            $membership = $this->commands->dispatch(
                new GrantMembershipCommand(
                    organizationId: $id,
                    userId: $userId,
                    role: Role::from($request->string('role')->toString()),
                ),
            );

            return (new MembershipResource($membership))
                ->response()
                ->setStatusCode(201);
        } catch (MembershipAlreadyExists $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
