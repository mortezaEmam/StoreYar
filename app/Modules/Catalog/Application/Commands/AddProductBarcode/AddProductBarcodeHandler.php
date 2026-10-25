<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\AddProductBarcode;

use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductBarcodeModel;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class AddProductBarcodeHandler implements CommandHandler
{
    public function handle(Command $command): ProductBarcodeModel
    {
        if (! $command instanceof AddProductBarcodeCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $barcode = trim($command->barcode);
        if ($barcode === '') {
            throw new \InvalidArgumentException('Barcode is required.');
        }

        $product = ProductModel::query()
            ->where('id', $command->productId)
            ->where('organization_id', $command->organizationId)
            ->first();

        if ($product === null) {
            throw new \DomainException('Product not found in this organization.');
        }

        $dup = ProductBarcodeModel::query()
            ->where('organization_id', $command->organizationId)
            ->where('barcode', $barcode)
            ->exists();

        if ($dup) {
            throw new \DomainException("Barcode already exists: {$barcode}");
        }

        if ($command->isPrimary) {
            ProductBarcodeModel::query()
                ->where('organization_id', $command->organizationId)
                ->where('product_id', $command->productId)
                ->update(['is_primary' => false]);
        }

        $row = new ProductBarcodeModel();
        $row->id = (new UlidGenerator())->generate();
        $row->organization_id = $command->organizationId;
        $row->product_id = $command->productId;
        $row->variant_id = $command->variantId;
        $row->barcode = $barcode;
        $row->type = $command->type;
        $row->unit_id = $command->unitId;
        $row->is_primary = $command->isPrimary;
        $row->save();

        return $row;
    }
}
