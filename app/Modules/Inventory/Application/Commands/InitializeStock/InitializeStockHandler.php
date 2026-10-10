<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\InitializeStock;

use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\ProductExistenceChecker;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Domain\Exceptions\ProductNotFoundForStock;
use StoreYar\Modules\Inventory\Domain\Exceptions\StockItemAlreadyExists;
use StoreYar\Modules\Inventory\Domain\ValueObjects\StockItemId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;


final class InitializeStockHandler implements CommandHandler
{
    public function __construct(
        private StockItemRepository $stockItems,
        private ProductExistenceChecker $products,
        private Clock $clock,
    ) {}

    public function handle(Command $command): StockItem
    {
        if (! $command instanceof InitializeStockCommand) {
            throw new \InvalidArgumentException(
                'InitializeStockHandler received an invalid command.',
            );
        }

        if (! $this->products->existsInOrganization(
            $command->organizationId,
            $command->productId,
        )) {
            throw new ProductNotFoundForStock($command->productId);
        }

        $existing = $this->stockItems->findByOrganizationAndProduct(
            $command->organizationId,
            $command->productId,
        );

        if ($existing !== null) {
            throw new StockItemAlreadyExists($command->productId);
        }

        $item = StockItem::create(
            id: StockItemId::generate(),
            organizationId: $command->organizationId,
            productId: $command->productId,
            quantity: $command->quantity,
            now: $this->clock->now(),
        );

        $this->stockItems->save($item);

        return $item;
    }
}
