<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Contracts;

interface AuditLogger
{
    /**
     * @param array<string, mixed> $payload
     */
    public function log(
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        ?string $organizationId = null,
        ?string $actorUserId = null,
        array $payload = [],
        ?string $ipAddress = null,
    ): void;
}
