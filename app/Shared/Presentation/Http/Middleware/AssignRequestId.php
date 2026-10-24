<?php

declare(strict_types=1);

namespace StoreYar\Shared\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::HEADER);
        $requestId = is_string($incoming) && $incoming !== ''
            ? substr($incoming, 0, 64)
            : (string) Str::ulid();

        $request->headers->set(self::HEADER, $requestId);
        $request->attributes->set('request_id', $requestId);

        Log::withContext(['request_id' => $requestId]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
