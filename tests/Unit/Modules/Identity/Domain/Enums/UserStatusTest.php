<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Domain\Enums;

use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Domain\Enums\UserStatus;

final class UserStatusTest extends TestCase
{
    public function test_active_status_is_active(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->isActive());
        $this->assertFalse(UserStatus::ACTIVE->isSuspended());
        $this->assertSame('active', UserStatus::ACTIVE->value);
    }

    public function test_suspended_status_is_suspended(): void
    {
        $this->assertTrue(UserStatus::SUSPENDED->isSuspended());
        $this->assertFalse(UserStatus::SUSPENDED->isActive());
        $this->assertSame('suspended', UserStatus::SUSPENDED->value);
    }

    public function test_status_can_be_restored_from_storage_value(): void
    {
        $this->assertSame(
            UserStatus::ACTIVE,
            UserStatus::from('active'),
        );

        $this->assertSame(
            UserStatus::SUSPENDED,
            UserStatus::from('suspended'),
        );
    }
}
