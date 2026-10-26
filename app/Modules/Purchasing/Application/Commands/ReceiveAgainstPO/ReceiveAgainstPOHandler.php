<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Application\Commands\ReceiveAgainstPO;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Inventory\Application\Commands\ReceiveStock\ReceiveStockCommand;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\GoodsReceiptLineModel;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\GoodsReceiptModel;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\PurchaseOrderLineModel;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\PurchaseOrderModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;
use StoreYar\Modules\Accounting\Application\Services\OperationalPostingService;

final class ReceiveAgainstPOHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
        private ?OperationalPostingService $accounting = null,
    ) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof ReceiveAgainstPOCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        if ($command->lines === []) {
            throw new \InvalidArgumentException('At least one line is required.');
        }

        $po = PurchaseOrderModel::query()
            ->where('id', $command->purchaseOrderId)
            ->where('organization_id', $command->organizationId)
            ->first();

        if ($po === null) {
            throw new \DomainException('Purchase order not found.');
        }

        if (! in_array($po->status, ['ORDERED', 'PARTIAL'], true)) {
            throw new \DomainException("Cannot receive against PO in status: {$po->status}");
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $po, $ids): array {
            $docNumber = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $docNumber = $gen->next(
                    organizationId: $command->organizationId,
                    documentType: 'GOODS_RECEIPT',
                    prefix: 'GRN-',
                );
            } catch (\Throwable) {
                $docNumber = 'GRN-' . substr((string) new Ulid(), -8);
            }

            $grn = new GoodsReceiptModel();
            $grn->id = $ids->generate();
            $grn->organization_id = $command->organizationId;
            $grn->purchase_order_id = $po->id;
            $grn->warehouse_id = $po->warehouse_id;
            $grn->document_number = $docNumber;
            $grn->status = 'POSTED';
            $grn->received_at = now();
            $grn->received_by = $command->receivedBy;
            $grn->notes = $command->notes;
            $grn->save();

            $lineResults = [];
            $totalCost = 0;
            foreach ($command->lines as $lineIn) {
                $lineId = $lineIn['purchase_order_line_id'] ?? null;
                $qty = (int) ($lineIn['quantity'] ?? 0);
                if (! is_string($lineId) || $qty <= 0) {
                    throw new \InvalidArgumentException('Each line needs purchase_order_line_id and positive quantity.');
                }

                /** @var PurchaseOrderLineModel|null $poLine */
                $poLine = PurchaseOrderLineModel::query()
                    ->where('id', $lineId)
                    ->where('purchase_order_id', $po->id)
                    ->lockForUpdate()
                    ->first();

                if ($poLine === null) {
                    throw new \DomainException("PO line not found: {$lineId}");
                }

                $remaining = (int) $poLine->quantity_ordered - (int) $poLine->quantity_received;
                if ($qty > $remaining) {
                    throw new \DomainException(
                        "Cannot receive {$qty}; remaining on line is {$remaining}."
                    );
                }

                $issue = $this->commands->dispatch(
                    new ReceiveStockCommand(
                        organizationId: $command->organizationId,
                        warehouseId: $po->warehouse_id,
                        productId: $poLine->product_id,
                        quantity: $qty,
                        reason: 'GRN ' . $docNumber,
                        documentType: 'GOODS_RECEIPT',
                        documentId: $grn->id,
                        createdBy: $command->receivedBy,
                        unitCostAmount: (int) $poLine->unit_cost_amount,
                        currency: $poLine->currency,
                    ),
                );

                $poLine->quantity_received = (int) $poLine->quantity_received + $qty;
                $poLine->save();

                $totalCost += (int) $poLine->unit_cost_amount * $qty;

                $grnLine = new GoodsReceiptLineModel();
                $grnLine->id = (string) new Ulid();
                $grnLine->goods_receipt_id = $grn->id;
                $grnLine->purchase_order_line_id = $poLine->id;
                $grnLine->product_id = $poLine->product_id;
                $grnLine->quantity = $qty;
                $grnLine->stock_movement_id = $issue['movement']->id;
                $grnLine->save();

                $lineResults[] = [
                    'purchase_order_line_id' => $poLine->id,
                    'product_id' => $poLine->product_id,
                    'quantity' => $qty,
                    'quantity_received_total' => $poLine->quantity_received,
                    'stock_movement_id' => $issue['movement']->id,
                    'quantity_after' => $issue['quantity_after'],
                ];
            }

            // Update PO status
            $allLines = PurchaseOrderLineModel::query()
                ->where('purchase_order_id', $po->id)
                ->get();

            $allReceived = $allLines->every(
                fn (PurchaseOrderLineModel $l) => (int) $l->quantity_received >= (int) $l->quantity_ordered
            );
            $anyReceived = $allLines->some(
                fn (PurchaseOrderLineModel $l) => (int) $l->quantity_received > 0
            );

            $po->status = $allReceived ? 'RECEIVED' : ($anyReceived ? 'PARTIAL' : $po->status);
            $po->version = (int) $po->version + 1;
            $po->save();


            try {
                $accounting = $this->accounting ?? app(OperationalPostingService::class);
                $accounting->postGoodsReceipt(
                    organizationId: $command->organizationId,
                    goodsReceiptId: $grn->id,
                    totalCostAmount: $totalCost,
                );
            } catch (\Throwable $e) {
                report($e);
            }


            return [
                'goods_receipt' => $grn->load('lines'),
                'purchase_order' => $po->fresh()->load('lines'),
                'lines' => $lineResults,
            ];
        });
    }
}
