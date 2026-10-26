<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Application\Commands\ReturnPurchase;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Accounting\Application\Services\OperationalPostingService;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\SupplierModel;
use StoreYar\Modules\Inventory\Application\Commands\IssueStock\IssueStockCommand;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\PurchaseReturnLineModel;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\PurchaseReturnModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class ReturnPurchaseHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof ReturnPurchaseCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }
        if ($command->lines === []) {
            throw new \InvalidArgumentException('At least one line required.');
        }

        $supplier = SupplierModel::query()
            ->where('id', $command->supplierId)
            ->where('organization_id', $command->organizationId)
            ->first();
        if ($supplier === null) {
            throw new \DomainException('Supplier not found.');
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

        return DB::transaction(function () use ($command, $supplier, $ids): array {
            $doc = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $doc = $gen->next($command->organizationId, 'PURCHASE_RETURN', 'PR-');
            } catch (\Throwable) {
                $doc = 'PR-' . substr((string) new Ulid(), -8);
            }

            $ret = new PurchaseReturnModel();
            $ret->id = $ids->generate();
            $ret->organization_id = $command->organizationId;
            $ret->purchase_order_id = $command->purchaseOrderId;
            $ret->supplier_id = $command->supplierId;
            $ret->warehouse_id = $command->warehouseId;
            $ret->document_number = $doc;
            $ret->status = 'POSTED';
            $ret->currency = 'IRR';
            $ret->reason = $command->reason;
            $ret->created_by = $command->createdBy;
            $ret->returned_at = now();
            $ret->total_amount = 0;
            $ret->save();

            $total = 0;
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
                $lineTotal = $unitCost * $qty;
                $total += $lineTotal;

                $issue = $this->commands->dispatch(
                    new IssueStockCommand(
                        organizationId: $command->organizationId,
                        warehouseId: $command->warehouseId,
                        productId: $productId,
                        quantity: $qty,
                        reason: 'Purchase return ' . $doc,
                        documentType: 'PURCHASE_RETURN',
                        documentId: $ret->id,
                        createdBy: $command->createdBy,
                    ),
                );

                $rl = new PurchaseReturnLineModel();
                $rl->id = (string) new Ulid();
                $rl->purchase_return_id = $ret->id;
                $rl->product_id = $productId;
                $rl->quantity = $qty;
                $rl->unit_cost_amount = $unitCost;
                $rl->line_total_amount = $lineTotal;
                $rl->stock_movement_id = $issue['movement']->id;
                $rl->save();

                $lineResults[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_cost_amount' => $unitCost,
                    'line_total_amount' => $lineTotal,
                    'stock_movement_id' => $issue['movement']->id,
                    'quantity_after' => $issue['quantity_after'],
                ];
            }

            $ret->total_amount = $total;
            $ret->save();

            // Reduce supplier AP outstanding
            $bal = max(0, (int) $supplier->outstanding_balance_amount - $total);
            $supplier->outstanding_balance_amount = $bal;
            $supplier->save();

            // Accounting: reverse GRN style — Debit AP, Credit Inventory
            if ($command->postAccounting && $total > 0) {
                try {
                    $posting = app(\StoreYar\Modules\Accounting\Application\Services\AccountLookup::class);
                    $ap = $posting->bySubtype($command->organizationId, 'AP');
                    $inv = $posting->bySubtype($command->organizationId, 'INVENTORY');
                    $this->commands->dispatch(
                        new \StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryCommand(
                            organizationId: $command->organizationId,
                            entryDate: now()->toDateString(),
                            lines: [
                                ['account_id' => $ap->id, 'debit_amount' => $total, 'credit_amount' => 0, 'memo' => 'Purchase return AP'],
                                ['account_id' => $inv->id, 'debit_amount' => 0, 'credit_amount' => $total, 'memo' => 'Purchase return inventory'],
                            ],
                            memo: 'Purchase return ' . $doc,
                            sourceType: 'PURCHASE_RETURN',
                            sourceId: $ret->id,
                            post: true,
                        ),
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return [
                'return' => $ret->load('lines'),
                'lines' => $lineResults,
            ];
        });
    }
}
