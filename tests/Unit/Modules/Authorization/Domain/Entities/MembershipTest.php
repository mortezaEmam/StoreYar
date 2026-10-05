<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Authorization\Domain\Entities;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Authorization\Domain\Entities\Membership;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\ValueObjects\MembershipId;

final class MembershipTest extends TestCase
{
    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-05 12:00:00', new DateTimeZone('UTC'));
    }

    public function test_it_creates_membership(): void
    {
        $id = MembershipId::generate();
        $now = $this->now();

        $membership = Membership::create(
            id: $id,
            organizationId: '01JORG00000000000000000001',
            userId: '01JOWNERUSER00000000000001',
            role: Role::OWNER,
            now: $now,
        );

        self::assertSame($id->value(), $membership->membershipId()->value());
        self::assertSame('01JORG00000000000000000001', $membership->organizationId());
        self::assertSame('01JOWNERUSER00000000000001', $membership->userId());
        self::assertTrue($membership->role()->isOwner());
        self::assertSame(0, $membership->version());
        self::assertSame($now, $membership->createdAt());
    }

    public function test_it_changes_role(): void
    {
        $membership = Membership::create(
            id: MembershipId::generate(),
            organizationId: '01JORG00000000000000000001',
            userId: '01JOWNERUSER00000000000001',
            role: Role::MEMBER,
            now: $this->now(),
        );

        $later = $this->now()->modify('+1 hour');
        $membership->changeRole(Role::ADMIN, $later);

        self::assertSame(Role::ADMIN, $membership->role());
        self::assertSame(1, $membership->version());
        self::assertSame($later, $membership->updatedAt());
    }

    public function test_it_rejects_empty_user_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Membership::create(
            id: MembershipId::generate(),
            organizationId: '01JORG00000000000000000001',
            userId: '  ',
            role: Role::MEMBER,
            now: $this->now(),
        );
    }
}
