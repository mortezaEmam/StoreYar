<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Concurrency;

use Illuminate\Database\ConnectionInterface;
use StoreYar\Shared\Application\Concurrency\VersionedUpdateResult;

final readonly class DatabaseVersionedUpdater
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    /**
     * @param array<string,mixed> $changes
     */
    public function update(
        string $table,
        string $id,
        int $expectedVersion,
        array $changes,
    ): VersionedUpdateResult {


        if ($table === '') {
            throw new \InvalidArgumentException(
                'Table name cannot be empty.',
            );
        }

        if ($id === '') {
            throw new \InvalidArgumentException(
                'Resource ID cannot be empty.',
            );
        }

        if ($expectedVersion < 0) {
            throw new \InvalidArgumentException(
                'Expected version must be zero or greater.',
            );
        }


        $nextVersion = $expectedVersion + 1;

        $updated = $this->connection
            ->table($table)
            ->where('id', $id)
            ->where('version', $expectedVersion)
            ->update([
                ...$changes,
                'version' => $nextVersion,
                'updated_at' => now(),
            ]);

        if ($updated === 1) {
            return VersionedUpdateResult::updated(
                nextVersion: $nextVersion,
            );
        }

        $current = $this->connection
            ->table($table)
            ->where('id', $id)
            ->value('version');

        if ($current === null) {
            throw new \RuntimeException(
                'Versioned resource was not found.',
            );
        }

        return VersionedUpdateResult::conflict(
            currentVersion: (int) $current,
        );
    }
}
