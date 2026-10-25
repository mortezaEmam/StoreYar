<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\IssueStock;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockItemModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockMovementModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use Symfony\Component\Uid\Ulid;

final class IssueStockHandler implements CommandHandler
{
    public function handle(Command $command): array
    {
        if (! $command instanceof IssueStockCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        if ($command->quantity <= 0) {
            throw new \InvalidArgumentException('Issue quantity must be positive.');
        }

        $wh = WarehouseModel::query()
            ->where('id', $command->warehouseId)
            ->where('organization_id', $command->organizationId)
            ->where('is_active', true)
            ->first();

        if ($wh === null) {
            throw new \DomainException('Warehouse not found or inactive.');
        }

        return DB::transaction(function () use ($command): array {
            $item = StockItemModel::query()
                ->where('organization_id', $command->organizationId)
                ->where('warehouse_id', $command->warehouseId)
                ->where('product_id', $command->productId)
                ->whereNull('variant_id')
                ->lockForUpdate()
                ->first();

            if ($item === null) {
                throw new \DomainException('No stock for this product in the warehouse.');
            }

            $available = (int) $item->quantity; // quantity acts as available after reserves in current model
            if ($available < $command->quantity) {
                throw new \DomainException("Insufficient stock. Available: {$available}");
            }

            $before = $available;
            $after = $before - $command->quantity;
            $item->quantity = $after;
            $item->version = (int) $item->version + 1;
            $item->save();

            $now = now();
            $movement = new StockMovementModel();
            $movement->id = (string) new Ulid();
            $movement->organization_id = $command->organizationId;
            $movement->warehouse_id = $command->warehouseId;
            $movement->stock_item_id = $item->id;
            $movement->product_id = $command->productId;
            $movement->movement_type = 'ISSUE';
            $movement->document_type = $command->documentType;
            $movement->document_id = $command->documentId;
            $movement->direction = 'OUT';
            $movement->quantity_delta = -$command->quantity;
            $movement->reserved_delta = 0;
            $movement->quantity_after = $after;
            $movement->reserved_after = (int) $item->reserved_quantity;
            $movement->base_quantity = $command->quantity;
            $movement->reason = $command->reason;
            $movement->created_by = $command->createdBy;
            $movement->event_id = (string) new Ulid();
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
