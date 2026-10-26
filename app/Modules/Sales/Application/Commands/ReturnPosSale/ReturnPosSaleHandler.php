<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\ReturnPosSale;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Inventory\Application\Commands\ReceiveStock\ReceiveStockCommand;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosReturnLineModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosReturnModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSaleLineModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSaleModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class ReturnPosSaleHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof ReturnPosSaleCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }
        if ($command->lines === []) {
            throw new \InvalidArgumentException('At least one line required.');
        }

        $sale = PosSaleModel::query()
            ->where('id', $command->posSaleId)
            ->where('organization_id', $command->organizationId)
            ->first();

        if ($sale === null) {
            throw new \DomainException('POS sale not found.');
        }
        if ($sale->status !== 'COMPLETED') {
            throw new \DomainException('Only completed sales can be returned.');
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $sale, $ids): array {
            $docNumber = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $docNumber = $gen->next($command->organizationId, 'POS_RETURN', 'RT-');
            } catch (\Throwable) {
                $docNumber = 'RT-' . substr((string) new Ulid(), -8);
            }

            $ret = new PosReturnModel();
            $ret->id = $ids->generate();
            $ret->organization_id = $command->organizationId;
            $ret->pos_sale_id = $sale->id;
            $ret->warehouse_id = $sale->warehouse_id;
            $ret->document_number = $docNumber;
            $ret->currency = $sale->currency;
            $ret->status = 'COMPLETED';
            $ret->reason = $command->reason;
            $ret->created_by = $command->createdBy;
            $ret->returned_at = now();
            $ret->total_amount = 0;
            $ret->save();

            $total = 0;
            $lineResults = [];

            foreach ($command->lines as $lineIn) {
                $productId = $lineIn['product_id'] ?? null;
                $qty = (int) ($lineIn['quantity'] ?? 0);
                $saleLineId = $lineIn['pos_sale_line_id'] ?? null;

                if (! is_string($productId) || $qty <= 0) {
                    throw new \InvalidArgumentException('product_id and positive quantity required.');
                }

                $unitPrice = 0;
                if (is_string($saleLineId) && $saleLineId !== '') {
                    $saleLine = PosSaleLineModel::query()
                        ->where('id', $saleLineId)
                        ->where('pos_sale_id', $sale->id)
                        ->first();
                    if ($saleLine === null) {
                        throw new \DomainException("Sale line not found: {$saleLineId}");
                    }
                    if ($saleLine->product_id !== $productId) {
                        throw new \DomainException('product_id does not match sale line.');
                    }
                    $alreadyReturned = (int) PosReturnLineModel::query()
                        ->where('pos_sale_line_id', $saleLineId)
                        ->sum('quantity');
                    if ($alreadyReturned + $qty > (int) $saleLine->quantity) {
                        throw new \DomainException(
                            "Return qty exceeds sold qty. Sold={$saleLine->quantity}, already returned={$alreadyReturned}"
                        );
                    }
                    $unitPrice = (int) $saleLine->unit_price_amount;
                } else {
                    $soldQty = (int) PosSaleLineModel::query()
                        ->where('pos_sale_id', $sale->id)
                        ->where('product_id', $productId)
                        ->sum('quantity');
                    $returnedQty = (int) DB::table('pos_return_lines as rl')
                        ->join('pos_returns as r', 'r.id', '=', 'rl.pos_return_id')
                        ->where('r.pos_sale_id', $sale->id)
                        ->where('rl.product_id', $productId)
                        ->sum('rl.quantity');
                    if ($returnedQty + $qty > $soldQty) {
                        throw new \DomainException("Return qty exceeds sold qty for product {$productId}");
                    }
                    $unitPrice = (int) (PosSaleLineModel::query()
                        ->where('pos_sale_id', $sale->id)
                        ->where('product_id', $productId)
                        ->value('unit_price_amount') ?? 0);
                }

                $lineTotal = $unitPrice * $qty;
                $total += $lineTotal;

                $recv = $this->commands->dispatch(
                    new ReceiveStockCommand(
                        organizationId: $command->organizationId,
                        warehouseId: $sale->warehouse_id,
                        productId: $productId,
                        quantity: $qty,
                        reason: 'POS return ' . $docNumber,
                        documentType: 'POS_RETURN',
                        documentId: $ret->id,
                        createdBy: $command->createdBy,
                    ),
                );

                $rl = new PosReturnLineModel();
                $rl->id = (string) new Ulid();
                $rl->pos_return_id = $ret->id;
                $rl->pos_sale_line_id = is_string($saleLineId) ? $saleLineId : null;
                $rl->product_id = $productId;
                $rl->quantity = $qty;
                $rl->unit_price_amount = $unitPrice;
                $rl->line_total_amount = $lineTotal;
                $rl->stock_movement_id = $recv['movement']->id;
                $rl->save();

                $lineResults[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price_amount' => $unitPrice,
                    'line_total_amount' => $lineTotal,
                    'stock_movement_id' => $recv['movement']->id,
                    'quantity_after' => $recv['quantity_after'],
                ];
            }

            $ret->total_amount = $total;
            $ret->save();

            return [
                'return' => $ret,
                'lines' => $lineResults,
            ];
        });
    }
}
