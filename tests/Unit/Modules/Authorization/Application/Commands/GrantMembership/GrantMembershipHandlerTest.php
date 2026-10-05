<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Authorization\Application\Commands\GrantMembership;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipCommand;
use StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipHandler;
use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Modules\Authorization\Domain\Entities\Membership;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\Exceptions\MembershipAlreadyExists;
use StoreYar\Modules\Authorization\Domain\ValueObjects\MembershipId;
use StoreYar\Shared\Domain\Contracts\Clock;

final class GrantMembershipHandlerTest extends TestCase
{
    public function test_it_grants_membership(): void
    {
        $now = new DateTimeImmutable('2026-10-05 12:00:00', new DateTimeZone('UTC'));

        $repository = $this->createMock(MembershipRepository::class);
        $repository->method('findByOrganizationAndUser')->willReturn(null);
        $repository->expects($this->once())->method('save');

        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($now);

        $handler = new GrantMembershipHandler($repository, $clock);

        $membership = $handler->handle(
            new GrantMembershipCommand(
                organizationId: '01JORG00000000000000000001',
                userId: '01JOWNERUSER00000000000001',
                role: Role::OWNER,
            ),
        );

        self::assertInstanceOf(Membership::class, $membership);
        self::assertTrue($membership->role()->isOwner());
        self::assertSame('01JORG00000000000000000001', $membership->organizationId());
    }

    public function test_it_rejects_duplicate_membership(): void
    {
        $existing = Membership::reconstitute(
            id: MembershipId::generate(),
            organizationId: '01JORG00000000000000000001',
            userId: '01JOWNERUSER00000000000001',
            role: Role::OWNER,
            createdAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            updatedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            version: 0,
        );

        $repository = $this->createMock(MembershipRepository::class);
        $repository->method('findByOrganizationAndUser')->willReturn($existing);
        $repository->expects($this->never())->method('save');

        $clock = $this->createMock(Clock::class);

        $handler = new GrantMembershipHandler($repository, $clock);

        $this->expectException(MembershipAlreadyExists::class);

        $handler->handle(
            new GrantMembershipCommand(
                organizationId: '01JORG00000000000000000001',
                userId: '01JOWNERUSER00000000000001',
                role: Role::OWNER,
            ),
        );
    }
}
