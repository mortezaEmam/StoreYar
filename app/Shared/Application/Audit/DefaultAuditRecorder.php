<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Audit;

use StoreYar\Shared\Application\Contracts\Audit\AuditEntry;
use StoreYar\Shared\Application\Contracts\Audit\AuditRecorder;

final class DefaultAuditRecorder implements AuditRecorder
{
    /**
     * @var list<AuditEntry>
     */
    private array $entries = [];

    public function record(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /**
     * @return list<AuditEntry>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
