<?php

declare(strict_types=1);

namespace StoreYar\Shared\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;
use StoreYar\Shared\Application\Queries\ListAuditLog\ListAuditLogQuery;

final class AuditLogController
{
    public function __construct(
        private QueryBus $queries,
        private BusinessContext $business,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $page = PageRequest::fromQuery(
            $request->query('page'),
            $request->query('per_page'),
        );

        /** @var PageResult $result */
        $result = $this->queries->ask(
            new ListAuditLogQuery(
                organizationId: $this->business->businessId(),
                page: $page,
                resourceType: $request->query('resource_type'),
                resourceId: $request->query('resource_id'),
            ),
        );

        return response()->json([
            'data' => $result->items,
            'meta' => $result->toMeta(),
        ]);
    }
}
