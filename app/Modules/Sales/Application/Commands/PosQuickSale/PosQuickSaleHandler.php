<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\PosQuickSale;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductBarcodeModel;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Modules\Inventory\Application\Commands\IssueStock\IssueStockCommand;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSaleLineModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSaleModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSessionModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;
use StoreYar\Modules\Accounting\Application\Services\OperationalPostingService;
final class PosQuickSaleHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
        private ?OperationalPostingService $accounting = null,
    ) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof PosQuickSaleCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        if ($command->lines === []) {
            throw new \InvalidArgumentException('At least one line is required.');
        }

        $session = PosSessionModel::query()
            ->where('id', $command->posSessionId)
            ->where('organization_id', $command->organizationId)
            ->first();

        if ($session === null || $session->status !== 'OPEN') {
            throw new \DomainException('Open POS session required.');
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $session, $ids): array {
            $resolved = [];
            $subtotal = 0;

            foreach ($command->lines as $line) {
                $qty = (int) ($line['quantity'] ?? 0);
                if ($qty <= 0) {
                    throw new \InvalidArgumentException('Line quantity must be positive.');
                }

                $productId = $line['product_id'] ?? null;
                $barcode = isset($line['barcode']) ? trim((string) $line['barcode']) : null;

                if ($productId === null && $barcode) {
                    $bc = ProductBarcodeModel::query()
                        ->where('organization_id', $command->organizationId)
                        ->where('barcode', $barcode)
                        ->first();
                    if ($bc === null) {
                        throw new \DomainException("Barcode not found: {$barcode}");
                    }
                    $productId = $bc->product_id;
                }

                if (! is_string($productId) || $productId === '') {
                    throw new \InvalidArgumentException('product_id or barcode required per line.');
                }

                $product = ProductModel::query()
                    ->where('id', $productId)
                    ->where('organization_id', $command->organizationId)
                    ->first();

                if ($product === null) {
                    throw new \DomainException("Product not found: {$productId}");
                }

                if (isset($product->is_sellable) && ! $product->is_sellable) {
                    throw new \DomainException("Product is not sellable: {$product->sku}");
                }

                $unitPrice = (int) $product->unit_price_amount;
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                $resolved[] = [
                    'product_id' => $productId,
                    'barcode' => $barcode,
                    'quantity' => $qty,
                    'unit_price_amount' => $unitPrice,
                    'line_total_amount' => $lineTotal,
                    'currency' => $product->currency ?? 'IRR',
                    'name' => $product->name,
                    'sku' => $product->sku,
                ];
            }

            $discount = max(0, $command->discountAmount);
            if ($discount > $subtotal) {
                throw new \InvalidArgumentException('Discount cannot exceed subtotal.');
            }
            $total = $subtotal - $discount;

            $saleId = $ids->generate();
            $docNumber = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $docNumber = $gen->next(
                    organizationId: $command->organizationId,
                    documentType: 'POS_SALE',
                    prefix: 'POS-',
                );
            } catch (\Throwable) {
                $docNumber = 'POS-' . substr($saleId, -8);
            }

            $sale = new PosSaleModel();
            $sale->id = $saleId;
            $sale->organization_id = $command->organizationId;
            $sale->pos_session_id = $session->id;
            $sale->warehouse_id = $session->warehouse_id;
            $sale->customer_id = $command->customerId;
            $sale->document_number = $docNumber;
            $sale->subtotal_amount = $subtotal;
            $sale->discount_amount = $discount;
            $sale->tax_amount = 0;
            $sale->total_amount = $total;
            $sale->currency = $session->currency;
            $sale->payment_method = strtoupper($command->paymentMethod);
            $sale->status = 'COMPLETED';
            $sale->created_by = $command->createdBy;
            $sale->sold_at = now();
            $sale->save();

            $lineResults = [];
            foreach ($resolved as $r) {
                $issue = $this->commands->dispatch(
                    new IssueStockCommand(
                        organizationId: $command->organizationId,
                        warehouseId: $session->warehouse_id,
                        productId: $r['product_id'],
                        quantity: $r['quantity'],
                        reason: 'POS sale ' . $docNumber,
                        documentType: 'POS_SALE',
                        documentId: $saleId,
                        createdBy: $command->createdBy,
                    ),
                );

                $line = new PosSaleLineModel();
                $line->id = (string) new Ulid();
                $line->pos_sale_id = $saleId;
                $line->product_id = $r['product_id'];
                $line->barcode = $r['barcode'];
                $line->quantity = $r['quantity'];
                $line->unit_price_amount = $r['unit_price_amount'];
                $line->line_total_amount = $r['line_total_amount'];
                $line->currency = $r['currency'];
                $line->stock_movement_id = $issue['movement']->id;
                $line->save();

                $lineResults[] = array_merge($r, [
                    'stock_movement_id' => $issue['movement']->id,
                    'quantity_after' => $issue['quantity_after'],
                ]);
            }


            try {
                $accounting = $this->accounting ?? app(OperationalPostingService::class);
                $accounting->postPosSale(
                    organizationId: $command->organizationId,
                    posSaleId: $saleId,
                    totalAmount: $total,
                    paymentMethod: strtoupper($command->paymentMethod),
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return [
                'sale' => $sale,
                'lines' => $lineResults,
            ];
        });
    }
}
