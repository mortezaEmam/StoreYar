<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\ArchiveProduct;

use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class ArchiveProductHandler implements CommandHandler
{
    public function __construct(
        private ProductRepository $products,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Product
    {
        if (! $command instanceof ArchiveProductCommand) {
            throw new \InvalidArgumentException(
                'ArchiveProductHandler received an invalid command.',
            );
        }

        $product = $this->products->findById(
            ProductId::fromString($command->productId),
        );

        if ($product === null || $product->organizationId() !== $command->organizationId) {
            throw new \InvalidArgumentException('Product not found.');
        }

        $product->archive($this->clock->now());
        $this->products->save($product);

        return $product;
    }
}
