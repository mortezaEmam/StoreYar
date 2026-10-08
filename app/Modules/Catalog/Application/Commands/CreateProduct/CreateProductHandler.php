<?php

// CreateProductHandler.php
namespace StoreYar\Modules\Catalog\Application\Commands\CreateProduct;

use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Domain\Exceptions\ProductSkuAlreadyExists;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateProductHandler implements CommandHandler
{
    public function __construct(
        private ProductRepository $products,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Product
    {
        if (! $command instanceof CreateProductCommand) {
            throw new \InvalidArgumentException(
                'CreateProductHandler received an invalid command.',
            );
        }

        if ($this->products->findByOrganizationAndSku(
                $command->organizationId,
                $command->sku,
            ) !== null) {
            throw new ProductSkuAlreadyExists($command->sku);
        }

        $product = Product::create(
            id: ProductId::generate(),
            organizationId: $command->organizationId,
            name: $command->name,
            sku: $command->sku,
            now: $this->clock->now(),
        );

        $this->products->save($product);

        return $product;
    }
}
