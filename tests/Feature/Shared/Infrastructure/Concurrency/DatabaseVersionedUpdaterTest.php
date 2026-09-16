<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Concurrency;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;
use Tests\TestCase;

final class DatabaseVersionedUpdaterTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('concurrency_test_records', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->unsignedBigInteger('version');
            $table->string('value', 255);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('concurrency_test_records');

        parent::tearDown();
    }

    public function test_stale_version_cannot_update_the_record(): void
    {
        $id = '01K5M8Q7N4A2B6C9D3E1F0G5HJ';

        DB::table('concurrency_test_records')->insert([
            'id' => $id,
            'version' => 1,
            'value' => 'initial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updater = new DatabaseVersionedUpdater(
            DB::connection(),
        );

        $first = $updater->update(
            table: 'concurrency_test_records',
            id: $id,
            expectedVersion: 1,
            changes: [
                'value' => 'first-update',
            ],
        );

        $this->assertTrue(
            $first->updatedSuccessfully(),
        );

        $this->assertSame(
            2,
            $first->nextVersion(),
        );

        $second = $updater->update(
            table: 'concurrency_test_records',
            id: $id,
            expectedVersion: 1,
            changes: [
                'value' => 'stale-update',
            ],
        );

        $this->assertFalse(
            $second->updatedSuccessfully(),
        );

        $this->assertSame(
            2,
            $second->nextVersion(),
        );

        $record = DB::table('concurrency_test_records')
            ->where('id', $id)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(2, (int) $record->version);
        $this->assertSame('first-update', $record->value);
    }

    public function test_missing_record_throws_not_found_error(): void
    {
        $updater = new DatabaseVersionedUpdater(
            DB::connection(),
        );

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'Versioned resource was not found.',
        );

        $updater->update(
            table: 'concurrency_test_records',
            id: '01K5M8Q7N4A2B6C9D3E1F0G5HJ',
            expectedVersion: 1,
            changes: [
                'value' => 'missing',
            ],
        );
    }

    public function test_versioned_update_is_rolled_back_with_outer_transaction(): void
    {
        $id = '01K5M8Q7N4A2B6C9D3E1F0G5HJ';

        DB::table('concurrency_test_records')->insert([
            'id' => $id,
            'version' => 1,
            'value' => 'initial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updater = new DatabaseVersionedUpdater(
            DB::connection(),
        );

        try {
            DB::transaction(function () use ($updater, $id): void {
                $result = $updater->update(
                    table: 'concurrency_test_records',
                    id: $id,
                    expectedVersion: 1,
                    changes: [
                        'value' => 'inside-transaction',
                    ],
                );

                $this->assertTrue(
                    $result->updatedSuccessfully(),
                );

                throw new \RuntimeException(
                    'Force transaction rollback.',
                );
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Force transaction rollback.',
                $exception->getMessage(),
            );
        }

        $record = DB::table('concurrency_test_records')
            ->where('id', $id)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(1, (int) $record->version);
        $this->assertSame('initial', $record->value);
    }

    public function test_concurrent_updates_with_same_version_allow_only_one_success(): void
    {
        $id = '01K5M8Q7N4A2B6C9D3E1F0G5HJ';

        DB::table('concurrency_test_records')->insert([
            'id' => $id,
            'version' => 1,
            'value' => 'initial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstConnection = DB::connection();
        $secondConnection = DB::connection();

        $firstUpdater = new DatabaseVersionedUpdater(
            $firstConnection,
        );

        $secondUpdater = new DatabaseVersionedUpdater(
            $secondConnection,
        );

        $first = $firstUpdater->update(
            table: 'concurrency_test_records',
            id: $id,
            expectedVersion: 1,
            changes: [
                'value' => 'first-wins',
            ],
        );

        $second = $secondUpdater->update(
            table: 'concurrency_test_records',
            id: $id,
            expectedVersion: 1,
            changes: [
                'value' => 'second-loses',
            ],
        );

        $this->assertTrue(
            $first->updatedSuccessfully(),
        );

        $this->assertFalse(
            $second->updatedSuccessfully(),
        );

        $this->assertSame(2, $first->nextVersion());
        $this->assertSame(2, $second->nextVersion());

        $record = DB::table('concurrency_test_records')
            ->where('id', $id)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(2, (int) $record->version);
        $this->assertSame('first-wins', $record->value);
    }
}
