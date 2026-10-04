<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\RenameOrganization\RenameOrganizationCommand;
use StoreYar\Modules\Organization\Application\Queries\GetOrganizationById\GetOrganizationByIdQuery;
use StoreYar\Modules\Organization\Application\Queries\ListOrganizationsByOwner\ListOrganizationsByOwnerQuery;
use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Exceptions\OrganizationAlreadyExists;
use StoreYar\Modules\Organization\Domain\Exceptions\OrganizationDomainException;
use StoreYar\Modules\Organization\Presentation\Http\Requests\CreateOrganizationRequest;
use StoreYar\Modules\Organization\Presentation\Http\Resources\OrganizationResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;

use StoreYar\Modules\Organization\Application\Commands\ActivateOrganization\ActivateOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\SuspendOrganization\SuspendOrganizationCommand;
use StoreYar\Shared\Application\Bus\Query\QueryBus;

final class OrganizationController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private SessionRepository $sessions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sessionId = $request->attributes->get('session_id');
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $organizations = $this->queries->ask(
            new ListOrganizationsByOwnerQuery(ownerUserId: $session->userId()),
        );

        return OrganizationResource::collection($organizations)
            ->response()
            ->setStatusCode(200);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $sessionId = $request->attributes->get('session_id');
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $organization = $this->queries->ask(
            new GetOrganizationByIdQuery(organizationId: $id),
        );

        if ($organization === null) {
            return response()->json(['message' => 'Organization not found.'], 404);
        }

        if ($organization->ownerUserId() !== $session->userId()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return (new OrganizationResource($organization))
            ->response()
            ->setStatusCode(200);
    }

    public function rename(CreateOrganizationRequest $request, string $id): JsonResponse
    {
        // می‌توانی RenameOrganizationRequest جدا بسازی با rule: name required
        $sessionId = $request->attributes->get('session_id');
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $organization = $this->commands->dispatch(
                new RenameOrganizationCommand(
                    organizationId: $id,
                    name: $request->string('name')->toString(),
                    actorUserId: $session->userId(),
                ),
            );

            return (new OrganizationResource($organization))->response();
        } catch (OrganizationAlreadyExists $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Not allowed.' ? 403 : 404;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function store(
        CreateOrganizationRequest $request,
    ): JsonResponse {
        /** @var SessionId $sessionId */
        $sessionId = $request->attributes->get('session_id');

        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            /** @var Organization $organization */
            $organization = $this->commands->dispatch(
                new CreateOrganizationCommand(
                    name: $request->string('name')->toString(),
                    ownerUserId: $session->userId(),
                ),
            );

            return (new OrganizationResource($organization))
                ->response()
                ->setStatusCode(201);
        } catch (OrganizationAlreadyExists $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (OrganizationDomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    public function suspend(Request $request, string $id): JsonResponse
    {
        /** @var SessionId $sessionId */
        $sessionId = $request->attributes->get('session_id');
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            /** @var Organization $organization */
            $organization = $this->commands->dispatch(
                new SuspendOrganizationCommand(
                    organizationId: $id,
                    actorUserId: $session->userId(),
                ),
            );

            return (new OrganizationResource($organization))->response();
        } catch (\InvalidArgumentException $e) {
            $status = match ($e->getMessage()) {
                'Not allowed.' => 403,
                'Organization not found.' => 404,
                default => 422,
            };

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function activate(Request $request, string $id): JsonResponse
    {
        /** @var SessionId $sessionId */
        $sessionId = $request->attributes->get('session_id');
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            /** @var Organization $organization */
            $organization = $this->commands->dispatch(
                new ActivateOrganizationCommand(
                    organizationId: $id,
                    actorUserId: $session->userId(),
                ),
            );

            return (new OrganizationResource($organization))->response();
        } catch (\InvalidArgumentException $e) {
            $status = match ($e->getMessage()) {
                'Not allowed.' => 403,
                'Organization not found.' => 404,
                default => 422,
            };

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
