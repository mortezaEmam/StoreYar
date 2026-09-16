<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Concurrency;

final readonly class VersionedUpdateResult
{
    private function __construct(
        private bool $updated,
        private int $nextVersion,
    ) {
    }

    public static function updated(int $nextVersion): self
    {
        return new self(
            updated: true,
            nextVersion: $nextVersion,
        );
    }

    public static function conflict(int $currentVersion): self
    {
        return new self(
            updated: false,
            nextVersion: $currentVersion,
        );
    }

    public function updatedSuccessfully(): bool
    {
        return $this->updated;
    }

    public function nextVersion(): int
    {
        return $this->nextVersion;
    }
}
