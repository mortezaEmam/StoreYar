<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Authorization;

use StoreYar\Shared\Application\Authorization\SimplePermission;
use Tests\TestCase;

final class SimplePermissionTest extends TestCase
{
    public function test_it_exposes_permission_code(): void
    {
        $permission = new SimplePermission('sales.create');

        $this->assertSame(
            'sales.create',
            $permission->code()
        );
    }

    public function test_it_rejects_empty_permission_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SimplePermission('');
    }
}
