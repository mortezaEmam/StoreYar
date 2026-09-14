<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Audit;

use DateTimeImmutable;
use StoreYar\Shared\Application\Audit\DefaultAuditRecorder;
use StoreYar\Shared\Application\Contracts\Audit\AuditEntry;
use Tests\TestCase;

final class DefaultAuditRecorderTest extends TestCase
{
    public function test_it_records_audit_entries(): void
    {
        $recorder = new DefaultAuditRecorder();

        $entry = new AuditEntry(
            id: '01AUDIT',
            businessId: '01BUSINESS',
            branchId: null,
            actorId: '01USER',
            actorType: 'user',
            action: 'user.updated',
            resourceType: 'user',
            resourceId: '01USER',
            operationId: '01OPERATION',
            correlationId: '01CORRELATION',
            occurredAt: new DateTimeImmutable('now'),
        );

        $recorder->record($entry);

        $this->assertCount(1, $recorder->entries());
        $this->assertSame($entry, $recorder->entries()[0]);
    }

    public function test_it_preserves_recording_order(): void
    {
        $recorder = new DefaultAuditRecorder();

        $first = new AuditEntry(
            id: '01FIRST',
            businessId: '01BUSINESS',
            branchId: null,
            actorId: '01USER',
            actorType: 'user',
            action: 'user.created',
            resourceType: 'user',
            resourceId: '01USER',
            operationId: '01OPERATION-FIRST',
            correlationId: '01CORRELATION',
            occurredAt: new DateTimeImmutable('now'),
        );

        $second = new AuditEntry(
            id: '01SECOND',
            businessId: '01BUSINESS',
            branchId: null,
            actorId: '01USER',
            actorType: 'user',
            action: 'user.updated',
            resourceType: 'user',
            resourceId: '01USER',
            operationId: '01OPERATION-SECOND',
            correlationId: '01CORRELATION',
            occurredAt: new DateTimeImmutable('now'),
        );

        $recorder->record($first);
        $recorder->record($second);

        $this->assertSame(
            [$first, $second],
            $recorder->entries()
        );
    }
}
