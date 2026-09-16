<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Concurrency;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class DatabaseVersionedUpdaterTest extends TestCase
{
    public function test_update_changes_version_and_value(): void
    {
        $builder = $this->createMock(Builder::class);

        $builder
            ->expects($this->exactly(2))
            ->method('where')
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(
                    function (array $changes): bool {
                        return $changes['value'] === 'updated'
                            && $changes['version'] === 6
                            && $changes['updated_at'] instanceof \DateTimeInterface;
                    },
                ),
            )
            ->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);

        $connection
            ->expects($this->once())
            ->method('table')
            ->with('concurrency_test_records')
            ->willReturn($builder);

        $updater = new DatabaseVersionedUpdater(
            $connection,
        );

        $result = $updater->update(
            table: 'concurrency_test_records',
            id: '01TEST',
            expectedVersion: 5,
            changes: [
                'value' => 'updated',
            ],
        );

        $this->assertTrue(
            $result->updatedSuccessfully(),
        );

        $this->assertSame(
            6,
            $result->nextVersion(),
        );
    }


    public function test_negative_expected_version_is_rejected(): void
    {
        $connection = $this->createMock(
            \Illuminate\Database\ConnectionInterface::class,
        );

        $updater = new \StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater(
            $connection,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected version must be zero or greater.',
        );

        $updater->update(
            table: 'concurrency_test_records',
            id: '01TEST',
            expectedVersion: -1,
            changes: [
                'value' => 'invalid',
            ],
        );
    }


    public function test_empty_table_is_rejected(): void
    {
        $connection = $this->createMock(
            \Illuminate\Database\ConnectionInterface::class,
        );

        $updater = new \StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater(
            $connection,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Table name cannot be empty.',
        );

        $updater->update(
            table: '',
            id: '01TEST',
            expectedVersion: 1,
            changes: [
                'value' => 'invalid',
            ],
        );
    }
}
