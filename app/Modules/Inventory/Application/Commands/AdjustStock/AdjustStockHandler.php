<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\AdjustStock;

use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientStock;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class AdjustStockHandler implements CommandHandler
{
    public function __construct(
        private StockItemRepository $stockItems,
        private Clock $clock,
    ) {}

    public function handle(Command $command): StockItem
    {
        if (! $command instanceof AdjustStockCommand) {
            throw new \InvalidArgumentException(
                'AdjustStockHandler received an invalid command.',
            );
        }

        if (! in_array($command->direction, ['increase', 'decrease'], true)) {
            throw new \InvalidArgumentException('Invalid stock direction.');
        }

        $item = $this->stockItems->findByOrganizationAndProduct(
            $command->organizationId,
            $command->productId,
        );

        if ($item === null) {
            throw new \InvalidArgumentException('Stock item not found.');
        }

        $now = $this->clock->now();

        if ($command->direction === 'increase') {
            $item->increase($command->amount, $now);
        } else {
            $item->decrease($command->amount, $now);
        }

        $this->stockItems->save($item);

        return $item;
    }
}
