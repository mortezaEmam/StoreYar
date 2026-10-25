<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\ReceiveStock;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockItemModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockMovementModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class ReceiveStockHandler implements CommandHandler
{
    public function handle(Command $command): array
    {
        if (! $command instanceof ReceiveStockCommand) {
            throw new \InvalidArgumentException('Invalid command for ReceiveStockHandler.');
        }

        if ($command->quantity <= 0) {
            throw new \InvalidArgumentException('Receive quantity must be positive.');
        }

        $warehouse = WarehouseModel::query()
            ->where('id', $command->warehouseId)
            ->where('organization_id', $command->organizationId)
            ->where('is_active', true)
            ->first();

        if ($warehouse === null) {
            throw new \DomainException('Warehouse not found or inactive in this organization.');
        }

        $product = ProductModel::query()
            ->where('id', $command->productId)
            ->where('organization_id', $command->organizationId)
            ->first();

        if ($product === null) {
            throw new \DomainException('Product not found in this organization.');
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $ids): array {
            /** @var StockItemModel|null $item */
            $item = StockItemModel::query()
                ->where('organization_id', $command->organizationId)
                ->where('warehouse_id', $command->warehouseId)
                ->where('product_id', $command->productId)
                ->whereNull('variant_id')
                ->lockForUpdate()
                ->first();

            if ($item === null) {
                $item = new StockItemModel();
                $item->id = $ids->generate();
                $item->organization_id = $command->organizationId;
                $item->warehouse_id = $command->warehouseId;
                $item->product_id = $command->productId;
                $item->variant_id = null;
                $item->quantity = 0;
                $item->reserved_quantity = 0;
                $item->version = 0;
                $item->save();

                // re-lock
                $item = StockItemModel::query()
                    ->whereKey($item->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $before = (int) $item->quantity;
            $after = $before + $command->quantity;
            $item->quantity = $after;
            $item->version = (int) $item->version + 1;
            $item->save();

            $movementId = (string) new Ulid();
            $eventId = (string) new Ulid();
            $now = now();

            $movement = new StockMovementModel();
            $movement->id = $movementId;
            $movement->organization_id = $command->organizationId;
            $movement->warehouse_id = $command->warehouseId;
            $movement->stock_item_id = $item->id;
            $movement->product_id = $command->productId;
            $movement->variant_id = null;
            $movement->movement_type = 'RECEIPT';
            $movement->document_type = $command->documentType;
            $movement->document_id = $command->documentId;
            $movement->document_line_id = null;
            $movement->direction = 'IN';
            $movement->quantity_delta = $command->quantity;
            $movement->reserved_delta = 0;
            $movement->quantity_after = $after;
            $movement->reserved_after = (int) $item->reserved_quantity;
            $movement->base_quantity = $command->quantity;
            $movement->unit_cost_amount = $command->unitCostAmount;
            $movement->currency = $command->currency;
            $movement->reason = $command->reason;
            $movement->created_by = $command->createdBy;
            $movement->event_id = $eventId;
            $movement->occurred_at = $now;
            $movement->created_at = $now;
            $movement->save();

            return [
                'stock_item' => $item,
                'movement' => $movement,
                'quantity_before' => $before,
                'quantity_after' => $after,
            ];
        });
    }
}
