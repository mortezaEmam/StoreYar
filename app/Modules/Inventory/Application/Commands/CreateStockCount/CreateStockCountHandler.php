<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\CreateStockCount;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockCountLineModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockCountModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockItemModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class CreateStockCountHandler implements CommandHandler
{
    public function __construct(
        private ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function handle(Command $command): StockCountModel
    {
        if (! $command instanceof CreateStockCountCommand) {
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

        return DB::transaction(function () use ($command, $ids): StockCountModel {
            $doc = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $doc = $gen->next($command->organizationId, 'STOCK_COUNT', 'CNT-');
            } catch (\Throwable) {
                $doc = 'CNT-' . substr((string) new Ulid(), -8);
            }

            $count = new StockCountModel();
            $count->id = $ids->generate();
            $count->organization_id = $command->organizationId;
            $count->warehouse_id = $command->warehouseId;
            $count->document_number = $doc;
            $count->status = 'DRAFT';
            $count->notes = $command->notes;
            $count->created_by = $command->createdBy;
            $count->counted_at = now();
            $count->save();

            foreach ($command->lines as $line) {
                $productId = $line['product_id'] ?? null;
                $counted = (int) ($line['counted_quantity'] ?? -1);
                if (! is_string($productId) || $counted < 0) {
                    throw new \InvalidArgumentException('product_id and counted_quantity (>=0) required.');
                }

                $product = ProductModel::query()
                    ->where('id', $productId)
                    ->where('organization_id', $command->organizationId)
                    ->first();
                if ($product === null) {
                    throw new \DomainException("Product not found: {$productId}");
                }

                $systemQty = (int) (StockItemModel::query()
                    ->where('organization_id', $command->organizationId)
                    ->where('warehouse_id', $command->warehouseId)
                    ->where('product_id', $productId)
                    ->value('quantity') ?? 0);

                $cl = new StockCountLineModel();
                $cl->id = (string) new Ulid();
                $cl->stock_count_id = $count->id;
                $cl->product_id = $productId;
                $cl->system_quantity = $systemQty;
                $cl->counted_quantity = $counted;
                $cl->variance_quantity = $counted - $systemQty;
                $cl->save();
            }

            return $count->load('lines');
        });
    }
}
