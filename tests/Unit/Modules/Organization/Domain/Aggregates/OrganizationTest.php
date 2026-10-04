<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Organization\Domain\Aggregates;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Enums\OrganizationStatus;
use StoreYar\Modules\Organization\Domain\Events\OrganizationCreated;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;

final class OrganizationTest extends TestCase
{
    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-04 12:00:00', new DateTimeZone('UTC'));
    }

    public function test_it_creates_an_active_organization(): void
    {
        $id = OrganizationId::generate();
        $now = $this->now();

        $organization = Organization::create(
            id: $id,
            name: 'My Store',
            ownerUserId: '01JOWNERUSER00000000000001',
            now: $now,
        );

        self::assertSame($id->value(), $organization->id());
        self::assertSame('My Store', $organization->name());
        self::assertSame('01JOWNERUSER00000000000001', $organization->ownerUserId());
        self::assertTrue($organization->status()->isActive());
        self::assertSame(0, $organization->version());
        self::assertSame($now, $organization->createdAt());
    }

    public function test_it_records_organization_created_event(): void
    {
        $organization = Organization::create(
            id: OrganizationId::generate(),
            name: 'My Store',
            ownerUserId: '01JOWNERUSER00000000000001',
            now: $this->now(),
        );

        $events = $organization->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OrganizationCreated::class, $events[0]);
        self::assertSame('My Store', $events[0]->name());
        self::assertSame('organization.organization', $events[0]->aggregateType());
    }

    public function test_it_can_be_suspended_and_activated(): void
    {
        $organization = Organization::create(
            id: OrganizationId::generate(),
            name: 'My Store',
            ownerUserId: '01JOWNERUSER00000000000001',
            now: $this->now(),
        );

        $organization->pullDomainEvents();

        $later = $this->now()->modify('+1 hour');
        $organization->suspend($later);

        self::assertTrue($organization->status()->isSuspended());
        self::assertSame(1, $organization->version());

        $organization->activate($later->modify('+1 hour'));

        self::assertTrue($organization->status()->isActive());
        self::assertSame(2, $organization->version());
    }

    public function test_it_rejects_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Organization::create(
            id: OrganizationId::generate(),
            name: '   ',
            ownerUserId: '01JOWNERUSER00000000000001',
            now: $this->now(),
        );
    }
}
