<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Queries\ListAuditLog;

use Illuminate\Database\ConnectionInterface;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use StoreYar\Shared\Application\Pagination\PageResult;

final class ListAuditLogHandler implements QueryHandler
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function handle(Query $query): PageResult
    {
        if (! $query instanceof ListAuditLogQuery) {
            throw new \InvalidArgumentException('Invalid query for ListAuditLogHandler.');
        }

        $base = $this->connection
            ->table('shared_audit_log')
            ->where('organization_id', $query->organizationId);

        if ($query->resourceType !== null) {
            $base->where('resource_type', $query->resourceType);
        }

        if ($query->resourceId !== null) {
            $base->where('resource_id', $query->resourceId);
        }

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->orderByDesc('occurred_at')
            ->offset($query->page->offset())
            ->limit($query->page->perPage)
            ->get()
            ->map(static function (object $row): array {
                return [
                    'id' => $row->id,
                    'organization_id' => $row->organization_id,
                    'actor_user_id' => $row->actor_user_id,
                    'action' => $row->action,
                    'resource_type' => $row->resource_type,
                    'resource_id' => $row->resource_id,
                    'payload' => $row->payload === null
                        ? null
                        : json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR),
                    'ip_address' => $row->ip_address,
                    'occurred_at' => $row->occurred_at,
                ];
            })
            ->all();

        return new PageResult(
            items: $rows,
            total: $total,
            page: $query->page->page,
            perPage: $query->page->perPage,
        );
    }
}
