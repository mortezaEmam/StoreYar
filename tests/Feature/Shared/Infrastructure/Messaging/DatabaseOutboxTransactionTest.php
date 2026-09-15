<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Messaging;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxMessage;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use StoreYar\Shared\Infrastructure\Messaging\DatabaseOutboxStore;
use Tests\TestCase;

final class DatabaseOutboxTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_change_and_outbox_are_rolled_back_together(): void
    {
        $idGenerator = new UlidGenerator();

        $operationId = $idGenerator->generate();
        $businessId = $idGenerator->generate();

        $message = new OutboxMessage(
            id: $idGenerator->generate(),
            eventId: $idGenerator->generate(),
            version: 1,
            eventType: 'sales.sale.created',
            businessId: $businessId,
            aggregateId: $idGenerator->generate(),
            aggregateType: 'sales.sale',
            operationId: $operationId,
            correlationId: $idGenerator->generate(),
            causationId: null,
            occurredAt: new \DateTimeImmutable(
                '2026-01-01 10:00:00+00:00',
            ),
            payload: [],
        );

        try {
            DB::transaction(function () use (
                $message,
                $businessId,
                $operationId,
            ): void {
                DB::table('users')->insert([
                    'name' => 'Transaction Test',
                    'email' => $operationId . '@example.com',
                    'password' => 'hashed-password',
                ]);

                app(DatabaseOutboxStore::class)->store($message);

                throw new \RuntimeException(
                    'Force transaction rollback.',
                );
            });
        } catch (\RuntimeException) {
            // Expected rollback.
        }

        $this->assertDatabaseMissing('users', [
            'email' => $operationId . '@example.com',
        ]);

        $this->assertDatabaseMissing(
            'integration_outbox_messages',
            [
                'event_id' => $message->eventId(),
            ],
        );
    }

    public function test_business_change_and_outbox_are_committed_together(): void
    {
        $idGenerator = new UlidGenerator();

        $operationId = $idGenerator->generate();
        $businessId = $idGenerator->generate();

        $message = new OutboxMessage(
            id: $idGenerator->generate(),
            eventId: $idGenerator->generate(),
            version: 1,
            eventType: 'sales.sale.created',
            businessId: $businessId,
            aggregateId: $idGenerator->generate(),
            aggregateType: 'sales.sale',
            operationId: $operationId,
            correlationId: $idGenerator->generate(),
            causationId: null,
            occurredAt: new \DateTimeImmutable(
                '2026-01-01 10:00:00+00:00',
            ),
            payload: [],
        );

        DB::transaction(function () use (
            $message,
            $operationId,
        ): void {
            DB::table('users')->insert([
                'name' => 'Transaction Commit Test',
                'email' => $operationId . '@example.com',
                'password' => 'hashed-password',
            ]);

            app(DatabaseOutboxStore::class)->store($message);
        });

        $this->assertDatabaseHas('users', [
            'email' => $operationId . '@example.com',
        ]);

        $this->assertDatabaseHas(
            'integration_outbox_messages',
            [
                'event_id' => $message->eventId(),
                'operation_id' => $message->operationId(),
            ],
        );
    }
}
