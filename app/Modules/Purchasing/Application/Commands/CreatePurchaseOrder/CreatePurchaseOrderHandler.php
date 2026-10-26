<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Application\Commands\CreatePurchaseOrder;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\SupplierModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\PurchaseOrderLineModel;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\PurchaseOrderModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class CreatePurchaseOrderHandler implements CommandHandler
{
    public function __construct(
        private ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function handle(Command $command): PurchaseOrderModel
    {
        if (! $command instanceof CreatePurchaseOrderCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        if ($command->lines === []) {
            throw new \InvalidArgumentException('At least one line is required.');
        }

        $supplier = SupplierModel::query()
            ->where('id', $command->supplierId)
            ->where('organization_id', $command->organizationId)
            ->where('is_active', true)
            ->first();
        if ($supplier === null) {
            throw new \DomainException('Supplier not found or inactive.');
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

        return DB::transaction(function () use ($command, $ids): PurchaseOrderModel {
            $subtotal = 0;
            $resolved = [];

            foreach ($command->lines as $line) {
                $productId = $line['product_id'] ?? null;
                $qty = (int) ($line['quantity'] ?? 0);
                if (! is_string($productId) || $productId === '' || $qty <= 0) {
                    throw new \InvalidArgumentException('Each line needs product_id and positive quantity.');
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

                $lineTotal = $unitCost * $qty;
                $subtotal += $lineTotal;
                $resolved[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_cost_amount' => $unitCost,
                    'line_total_amount' => $lineTotal,
                ];
            }

            $docNumber = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $docNumber = $gen->next(
                    organizationId: $command->organizationId,
                    documentType: 'PURCHASE_ORDER',
                    prefix: 'PO-',
                );
            } catch (\Throwable) {
                $docNumber = 'PO-' . substr((string) new Ulid(), -8);
            }

            $po = new PurchaseOrderModel();
            $po->id = $ids->generate();
            $po->organization_id = $command->organizationId;
            $po->supplier_id = $command->supplierId;
            $po->warehouse_id = $command->warehouseId;
            $po->branch_id = $command->branchId;
            $po->document_number = $docNumber;
            $po->status = $command->submit ? 'ORDERED' : 'DRAFT';
            $po->currency = strtoupper($command->currency);
            $po->subtotal_amount = $subtotal;
            $po->total_amount = $subtotal;
            $po->notes = $command->notes;
            $po->ordered_at = $command->submit ? now() : null;
            $po->expected_at = $command->expectedAt ? new \DateTimeImmutable($command->expectedAt) : null;
            $po->created_by = $command->createdBy;
            $po->version = 0;
            $po->save();

            foreach ($resolved as $r) {
                $line = new PurchaseOrderLineModel();
                $line->id = (string) new Ulid();
                $line->purchase_order_id = $po->id;
                $line->product_id = $r['product_id'];
                $line->quantity_ordered = $r['quantity'];
                $line->quantity_received = 0;
                $line->unit_cost_amount = $r['unit_cost_amount'];
                $line->line_total_amount = $r['line_total_amount'];
                $line->currency = $po->currency;
                $line->save();
            }

            return $po->load('lines');
        });
    }
}
