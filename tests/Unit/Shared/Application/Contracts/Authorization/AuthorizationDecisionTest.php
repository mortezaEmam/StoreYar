<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Contracts\Authorization;

use StoreYar\Shared\Application\Contracts\AuthorizationDecision;
use Tests\TestCase;

final class AuthorizationDecisionTest extends TestCase
{
    public function test_allow_creates_allowed_decision(): void
    {
        $decision = AuthorizationDecision::allow();

        $this->assertTrue($decision->allowed());
        $this->assertFalse($decision->denied());
        $this->assertSame(
            'authorization.allowed',
            $decision->code()
        );
        $this->assertNull($decision->reason());
    }

    public function test_allow_can_have_custom_code_and_reason(): void
    {
        $decision = AuthorizationDecision::allow(
            code: 'authorization.owner',
            reason: 'User owns the resource.',
        );

        $this->assertTrue($decision->allowed());
        $this->assertSame(
            'authorization.owner',
            $decision->code()
        );
        $this->assertSame(
            'User owns the resource.',
            $decision->reason()
        );
    }

    public function test_deny_creates_denied_decision(): void
    {
        $decision = AuthorizationDecision::deny();

        $this->assertFalse($decision->allowed());
        $this->assertTrue($decision->denied());
        $this->assertSame(
            'authorization.permission_denied',
            $decision->code()
        );
        $this->assertNull($decision->reason());
    }

    public function test_deny_can_have_custom_code_and_reason(): void
    {
        $decision = AuthorizationDecision::deny(
            code: 'authorization.branch_access_denied',
            reason: 'User has no access to this branch.',
        );

        $this->assertTrue($decision->denied());
        $this->assertSame(
            'authorization.branch_access_denied',
            $decision->code()
        );
        $this->assertSame(
            'User has no access to this branch.',
            $decision->reason()
        );
    }
}
