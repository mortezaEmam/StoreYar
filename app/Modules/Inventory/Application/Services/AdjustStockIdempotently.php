<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Services;

use DateTimeImmutable;
use DateTimeZone;
use StoreYar\Modules\Inventory\Application\Commands\AdjustStock\AdjustStockCommand;
use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Application\Idempotency\IdempotencyKey;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;
use Symfony\Component\Uid\Ulid;

final class AdjustStockIdempotently
{
    private const OPERATION_TYPE = 'inventory.stock.adjust';

    private const STATUS_STARTED = 'started';

    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        private CommandBus $commands,
        private StockItemRepository $stockItems,
        private IdempotencyStore $idempotency,
    ) {}

    /**
     * @return array{item: StockItem, replayed: bool}
     */
    public function execute(
        string $organizationId,
        string $productId,
        int $amount,
        string $direction,
        string $idempotencyKey,
    ): array {
        $operationId = IdempotencyKey::operationId(
            self::OPERATION_TYPE,
            $organizationId,
            $productId,
            $direction,
            (string) $amount,
            $idempotencyKey,
        );

        $existing = $this->idempotency->find($organizationId, $operationId);

        if ($existing !== null && $existing->status() === self::STATUS_COMPLETED) {
            $item = $this->stockItems->findByOrganizationAndProduct(
                $organizationId,
                $productId,
            );

            if ($item === null) {
                throw new \RuntimeException('Idempotent adjust result references missing stock.');
            }

            return ['item' => $item, 'replayed' => true];
        }

        if ($existing !== null && $existing->status() === self::STATUS_STARTED) {
            throw new IdempotencyConflictException(
                'A request with this Idempotency-Key is already being processed.',
            );
        }

        $claim = new IdempotencyRecord(
            id: (string) new Ulid(),
            businessId: $organizationId,
            operationId: $operationId,
            operationType: self::OPERATION_TYPE,
            status: self::STATUS_STARTED,
            response: [],
            completedAt: null,
        );

        if (! $this->idempotency->claim($claim)) {
            throw new IdempotencyConflictException(
                'A request with this Idempotency-Key is already being processed.',
            );
        }

        try {
            /** @var StockItem $item */
            $item = $this->commands->dispatch(
                new AdjustStockCommand(
                    organizationId: $organizationId,
                    productId: $productId,
                    amount: $amount,
                    direction: $direction,
                ),
            );

            $this->idempotency->store(
                new IdempotencyRecord(
                    id: $claim->id(),
                    businessId: $organizationId,
                    operationId: $operationId,
                    operationType: self::OPERATION_TYPE,
                    status: self::STATUS_COMPLETED,
                    response: [
                        'product_id' => $productId,
                        'quantity' => $item->quantity(),
                    ],
                    completedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
                ),
            );

            return ['item' => $item, 'replayed' => false];
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
