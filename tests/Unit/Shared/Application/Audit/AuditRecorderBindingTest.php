<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Audit;

use StoreYar\Shared\Application\Audit\DefaultAuditRecorder;
use StoreYar\Shared\Application\Contracts\Audit\AuditRecorder;
use Tests\TestCase;

final class AuditRecorderBindingTest extends TestCase
{
    public function test_audit_recorder_is_resolved_from_container(): void
    {
        $recorder = $this->app->make(AuditRecorder::class);

        $this->assertInstanceOf(
            DefaultAuditRecorder::class,
            $recorder
        );
    }

    public function test_audit_recorder_is_scoped(): void
    {
        $first = $this->app->make(AuditRecorder::class);
        $second = $this->app->make(AuditRecorder::class);

        $this->assertSame($first, $second);
    }
}
