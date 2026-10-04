<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionQuery;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateSession
{
    public function __construct(
        private QueryBus $queries,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $sessionId = $this->queries->ask(
            new ValidateSessionQuery(token: $token),
        );

        if (! $sessionId instanceof SessionId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->attributes->set('session_id', $sessionId);
        $request->attributes->set('session_token', $token);

        return $next($request);
    }
}
