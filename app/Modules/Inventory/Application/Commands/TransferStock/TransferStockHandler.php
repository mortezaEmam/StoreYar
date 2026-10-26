<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\TransferStock;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Inventory\Application\Commands\IssueStock\IssueStockCommand;
use StoreYar\Modules\Inventory\Application\Commands\ReceiveStock\ReceiveStockCommand;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class TransferStockHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof TransferStockCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        if ($command->fromWarehouseId === $command->toWarehouseId) {
            throw new \InvalidArgumentException('Source and destination warehouses must differ.');
        }
        if ($command->lines === []) {
            throw new \InvalidArgumentException('At least one line required.');
        }

        foreach ([$command->fromWarehouseId, $command->toWarehouseId] as $wid) {
            $ok = WarehouseModel::query()
                ->where('id', $wid)
                ->where('organization_id', $command->organizationId)
                ->where('is_active', true)
                ->exists();
            if (! $ok) {
                throw new \DomainException("Warehouse not found or inactive: {$wid}");
            }
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $ids): array {
            $docNumber = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $docNumber = $gen->next($command->organizationId, 'STOCK_TRANSFER', 'TR-');
            } catch (\Throwable) {
                $docNumber = 'TR-' . substr((string) new Ulid(), -8);
            }

            $transferId = $ids->generate();
            $now = now();

            DB::table('inventory_stock_transfers')->insert([
                'id' => $transferId,
                'organization_id' => $command->organizationId,
                'from_warehouse_id' => $command->fromWarehouseId,
                'to_warehouse_id' => $command->toWarehouseId,
                'document_number' => $docNumber,
                'status' => 'POSTED',
                'notes' => $command->notes,
                'created_by' => $command->createdBy,
                'transferred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $lineResults = [];
            foreach ($command->lines as $line) {
                $productId = $line['product_id'] ?? null;
                $qty = (int) ($line['quantity'] ?? 0);
                if (! is_string($productId) || $qty <= 0) {
                    throw new \InvalidArgumentException('Each line needs product_id and positive quantity.');
                }

                $out = $this->commands->dispatch(
                    new IssueStockCommand(
                        organizationId: $command->organizationId,
                        warehouseId: $command->fromWarehouseId,
                        productId: $productId,
                        quantity: $qty,
                        reason: 'Transfer ' . $docNumber,
                        documentType: 'STOCK_TRANSFER',
                        documentId: $transferId,
                        createdBy: $command->createdBy,
                    ),
                );

                $in = $this->commands->dispatch(
                    new ReceiveStockCommand(
                        organizationId: $command->organizationId,
                        warehouseId: $command->toWarehouseId,
                        productId: $productId,
                        quantity: $qty,
                        reason: 'Transfer ' . $docNumber,
                        documentType: 'STOCK_TRANSFER',
                        documentId: $transferId,
                        createdBy: $command->createdBy,
                    ),
                );

                $lineId = (string) new Ulid();
                DB::table('inventory_stock_transfer_lines')->insert([
                    'id' => $lineId,
                    'stock_transfer_id' => $transferId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'out_movement_id' => $out['movement']->id,
                    'in_movement_id' => $in['movement']->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $lineResults[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'out_movement_id' => $out['movement']->id,
                    'in_movement_id' => $in['movement']->id,
                    'from_qty_after' => $out['quantity_after'],
                    'to_qty_after' => $in['quantity_after'],
                ];
            }

            return [
                'id' => $transferId,
                'document_number' => $docNumber,
                'from_warehouse_id' => $command->fromWarehouseId,
                'to_warehouse_id' => $command->toWarehouseId,
                'lines' => $lineResults,
            ];
        });
    }
}
