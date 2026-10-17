<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Audit;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use StoreYar\Shared\Domain\Contracts\AuditLogger;
use Symfony\Component\Uid\Ulid;

final class DatabaseAuditLogger implements AuditLogger
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function log(
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        ?string $organizationId = null,
        ?string $actorUserId = null,
        array $payload = [],
        ?string $ipAddress = null,
    ): void {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $this->connection->table('shared_audit_log')->insert([
            'id' => (string) new Ulid(),
            'organization_id' => $organizationId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'payload' => $payload === []
                ? null
                : json_encode($payload, JSON_THROW_ON_ERROR),
            'ip_address' => $ipAddress,
            'occurred_at' => $now->format('Y-m-d H:i:s'),
            'created_at' => $now->format('Y-m-d H:i:s'),
        ]);
    }
}
