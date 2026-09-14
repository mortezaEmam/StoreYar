<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Audit;

interface AuditRecorder
{
    public function record(AuditEntry $entry): void;
}
