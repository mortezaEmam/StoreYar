<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionCommand;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdQuery;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionQuery;
use StoreYar\Modules\Identity\Application\Results\AuthenticationResult;
use StoreYar\Modules\Identity\Application\Results\SessionRotationResult;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Identity\Presentation\Http\Requests\LoginRequest;
use StoreYar\Modules\Identity\Presentation\Http\Resources\AuthenticationResource;
use StoreYar\Modules\Identity\Presentation\Http\Resources\UserResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;

final class AuthController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private SessionRepository $sessions,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            /** @var AuthenticationResult $result */
            $result = $this->commands->dispatch(
                new AuthenticateUserCommand(
                    email: $request->string('email')->toString(),
                    password: $request->string('password')->toString(),
                ),
            );

            return (new AuthenticationResource($result))
                ->response()
                ->setStatusCode(200);
        } catch (\InvalidArgumentException) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var SessionId $sessionId */
        $sessionId = $request->attributes->get('session_id');

        $this->commands->dispatch(
            new RevokeSessionCommand(sessionId: $sessionId),
        );

        return response()->json([
            'message' => 'Logged out successfully.',
        ], 200);
    }

    public function rotate(Request $request): JsonResponse
    {
        /** @var SessionId $sessionId */
        $sessionId = $request->attributes->get('session_id');

        try {
            /** @var SessionRotationResult $result */
            $result = $this->commands->dispatch(
                new RotateSessionCommand(currentSessionId: $sessionId),
            );

            return (new AuthenticationResource($result))
                ->response()
                ->setStatusCode(200);
        } catch (\InvalidArgumentException) {
            return response()->json([
                'message' => 'Invalid session.',
            ], 401);
        }
    }

    public function me(Request $request): JsonResponse
    {
        /** @var SessionId $sessionId */
        $sessionId = $request->attributes->get('session_id');

        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /** @var User|null $user */
        $user = $this->queries->ask(
            new GetUserByIdQuery(userId: $session->userId()),
        );

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return (new UserResource($user))
            ->response()
            ->setStatusCode(200);
    }
}
