<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Exceptions\OrganizationAlreadyExists;
use StoreYar\Modules\Organization\Domain\Exceptions\OrganizationDomainException;
use StoreYar\Modules\Organization\Presentation\Http\Requests\CreateOrganizationRequest;
use StoreYar\Modules\Organization\Presentation\Http\Resources\OrganizationResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;

final class OrganizationController
{
    public function __construct(
        private CommandBus $commands,
        private SessionRepository $sessions,
    ) {}

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
}
