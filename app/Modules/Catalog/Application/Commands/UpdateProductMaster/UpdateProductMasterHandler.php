<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\UpdateProductMaster;

use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class UpdateProductMasterHandler implements CommandHandler
{
    public function handle(Command $command): ProductModel
    {
        if (! $command instanceof UpdateProductMasterCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $product = ProductModel::query()
            ->where('id', $command->productId)
            ->where('organization_id', $command->organizationId)
            ->first();

        if ($product === null) {
            throw new \DomainException('Product not found in this organization.');
        }

        $map = [
            'short_name' => $command->shortName,
            'description' => $command->description,
            'brand_id' => $command->brandId,
            'category_id' => $command->categoryId,
            'base_unit_id' => $command->baseUnitId,
            'sale_unit_id' => $command->saleUnitId,
            'purchase_unit_id' => $command->purchaseUnitId,
            'cost_price_amount' => $command->costPriceAmount,
            'is_sellable' => $command->isSellable,
            'is_purchasable' => $command->isPurchasable,
            'is_stockable' => $command->isStockable,
            'is_taxable' => $command->isTaxable,
            'min_stock' => $command->minStock,
            'max_stock' => $command->maxStock,
            'reorder_point' => $command->reorderPoint,
            'reorder_qty' => $command->reorderQty,
            'track_lot' => $command->trackLot,
            'track_serial' => $command->trackSerial,
            'track_expiry' => $command->trackExpiry,
        ];

        foreach ($map as $column => $value) {
            if ($value !== null) {
                $product->{$column} = $value;
            }
        }

        $product->version = (int) $product->version + 1;
        $product->save();

        return $product;
    }
}
