<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\OpeningStock;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Modules\Inventory\Application\Commands\ReceiveStock\ReceiveStockCommand;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class OpeningStockHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof OpeningStockCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }
        if ($command->lines === []) {
            throw new \InvalidArgumentException('At least one line required.');
        }

        $wh = WarehouseModel::query()
            ->where('id', $command->warehouseId)
            ->where('organization_id', $command->organizationId)
            ->where('is_active', true)
            ->first();
        if ($wh === null) {
            throw new \DomainException('Warehouse not found or inactive.');
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $ids): array {
            $doc = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $doc = $gen->next($command->organizationId, 'OPENING_STOCK', 'OS-');
            } catch (\Throwable) {
                $doc = 'OS-' . substr((string) new Ulid(), -8);
            }

            $headerId = $ids->generate();
            $now = now();

            DB::table('inventory_opening_stocks')->insert([
                'id' => $headerId,
                'organization_id' => $command->organizationId,
                'warehouse_id' => $command->warehouseId,
                'document_number' => $doc,
                'status' => 'POSTED',
                'notes' => $command->notes,
                'created_by' => $command->createdBy,
                'posted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $lineResults = [];
            foreach ($command->lines as $line) {
                $productId = $line['product_id'] ?? null;
                $qty = (int) ($line['quantity'] ?? 0);
                if (! is_string($productId) || $qty <= 0) {
                    throw new \InvalidArgumentException('product_id and positive quantity required.');
                }

                $product = ProductModel::query()
                    ->where('id', $productId)
                    ->where('organization_id', $command->organizationId)
                    ->first();
                if ($product === null) {
                    throw new \DomainException("Product not found: {$productId}");
                }

                $unitCost = array_key_exists('unit_cost_amount', $line)
                    ? (int) $line['unit_cost_amount']
                    : (int) ($product->cost_price_amount ?? 0);

                $recv = $this->commands->dispatch(
                    new ReceiveStockCommand(
                        organizationId: $command->organizationId,
                        warehouseId: $command->warehouseId,
                        productId: $productId,
                        quantity: $qty,
                        reason: 'Opening stock ' . $doc,
                        documentType: 'OPENING_STOCK',
                        documentId: $headerId,
                        createdBy: $command->createdBy,
                        unitCostAmount: $unitCost,
                    ),
                );

                $lineId = (string) new Ulid();
                DB::table('inventory_opening_stock_lines')->insert([
                    'id' => $lineId,
                    'opening_stock_id' => $headerId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_cost_amount' => $unitCost,
                    'stock_movement_id' => $recv['movement']->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $lineResults[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_cost_amount' => $unitCost,
                    'stock_movement_id' => $recv['movement']->id,
                    'quantity_after' => $recv['quantity_after'],
                ];
            }

            return [
                'id' => $headerId,
                'document_number' => $doc,
                'warehouse_id' => $command->warehouseId,
                'lines' => $lineResults,
            ];
        });
    }
}
