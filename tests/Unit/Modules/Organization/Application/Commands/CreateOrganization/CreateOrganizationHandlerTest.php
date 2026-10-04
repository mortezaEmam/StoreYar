<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Organization\Application\Commands\CreateOrganization;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationHandler;
use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\Exceptions\OrganizationAlreadyExists;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateOrganizationHandlerTest extends TestCase
{
    public function test_it_creates_organization(): void
    {
        $now = new DateTimeImmutable('2026-10-04 12:00:00', new DateTimeZone('UTC'));

        $repository = $this->createMock(OrganizationRepository::class);
        $repository->method('findByName')->with('My Store')->willReturn(null);
        $repository->expects($this->once())->method('save');

        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($now);

        $handler = new CreateOrganizationHandler($repository, $clock);

        $organization = $handler->handle(
            new CreateOrganizationCommand(
                name: 'My Store',
                ownerUserId: '01JOWNERUSER00000000000001',
            ),
        );

        self::assertInstanceOf(Organization::class, $organization);
        self::assertSame('My Store', $organization->name());
        self::assertSame('01JOWNERUSER00000000000001', $organization->ownerUserId());
    }

    public function test_it_rejects_duplicate_name(): void
    {
        $existing = Organization::reconstitute(
            id: OrganizationId::generate(),
            name: 'My Store',
            ownerUserId: '01JOWNERUSER00000000000001',
            createdAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            updatedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            status: \StoreYar\Modules\Organization\Domain\Enums\OrganizationStatus::ACTIVE,
            version: 0,
        );

        $repository = $this->createMock(OrganizationRepository::class);
        $repository->method('findByName')->willReturn($existing);
        $repository->expects($this->never())->method('save');

        $clock = $this->createMock(Clock::class);

        $handler = new CreateOrganizationHandler($repository, $clock);

        $this->expectException(OrganizationAlreadyExists::class);

        $handler->handle(
            new CreateOrganizationCommand(
                name: 'My Store',
                ownerUserId: '01JOWNERUSER00000000000002',
            ),
        );
    }
}
