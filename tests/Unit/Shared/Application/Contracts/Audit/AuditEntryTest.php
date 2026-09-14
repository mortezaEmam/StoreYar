<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Contracts\Audit;

use DateTimeImmutable;
use StoreYar\Shared\Application\Contracts\Audit\AuditEntry;
use Tests\TestCase;

final class AuditEntryTest extends TestCase
{
    public function test_it_exposes_audit_data(): void
    {
        $occurredAt = new DateTimeImmutable('2026-01-01T12:00:00+00:00');

        $entry = new AuditEntry(
            id: '01AUDIT',
            businessId: '01BUSINESS',
            branchId: '01BRANCH',
            actorId: '01USER',
            actorType: 'user',
            action: 'sale.created',
            resourceType: 'sale',
            resourceId: '01SALE',
            operationId: '01OPERATION',
            correlationId: '01CORRELATION',
            occurredAt: $occurredAt,
            metadata: [
                'source' => 'pos',
            ],
        );

        $this->assertSame('01AUDIT', $entry->id());
        $this->assertSame('01BUSINESS', $entry->businessId());
        $this->assertSame('01BRANCH', $entry->branchId());
        $this->assertSame('01USER', $entry->actorId());
        $this->assertSame('user', $entry->actorType());
        $this->assertSame('sale.created', $entry->action());
        $this->assertSame('sale', $entry->resourceType());
        $this->assertSame('01SALE', $entry->resourceId());
        $this->assertSame('01OPERATION', $entry->operationId());
        $this->assertSame('01CORRELATION', $entry->correlationId());
        $this->assertSame($occurredAt, $entry->occurredAt());
        $this->assertSame(
            ['source' => 'pos'],
            $entry->metadata()
        );
    }

    public function test_it_rejects_empty_required_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AuditEntry(
            id: '',
            businessId: '01BUSINESS',
            branchId: null,
            actorId: null,
            actorType: 'system',
            action: 'sale.created',
            resourceType: 'sale',
            resourceId: null,
            operationId: '01OPERATION',
            correlationId: '01CORRELATION',
            occurredAt: new DateTimeImmutable(),
        );
    }
}
