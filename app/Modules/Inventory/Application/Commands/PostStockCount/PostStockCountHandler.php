<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\PostStockCount;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Inventory\Application\Commands\IssueStock\IssueStockCommand;
use StoreYar\Modules\Inventory\Application\Commands\ReceiveStock\ReceiveStockCommand;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockCountLineModel;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockCountModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class PostStockCountHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
    ) {}

    public function handle(Command $command): StockCountModel
    {
        if (! $command instanceof PostStockCountCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $count = StockCountModel::query()
            ->where('id', $command->stockCountId)
            ->where('organization_id', $command->organizationId)
            ->with('lines')
            ->first();

        if ($count === null) {
            throw new \DomainException('Stock count not found.');
        }
        if ($count->status !== 'DRAFT') {
            throw new \DomainException("Cannot post stock count in status: {$count->status}");
        }

        return DB::transaction(function () use ($command, $count): StockCountModel {
            foreach ($count->lines as $line) {
                /** @var StockCountLineModel $line */
                $variance = (int) $line->variance_quantity;
                if ($variance === 0) {
                    continue;
                }

                if ($variance > 0) {
                    $result = $this->commands->dispatch(
                        new ReceiveStockCommand(
                            organizationId: $command->organizationId,
                            warehouseId: $count->warehouse_id,
                            productId: $line->product_id,
                            quantity: $variance,
                            reason: 'Stock count ' . $count->document_number,
                            documentType: 'STOCK_COUNT',
                            documentId: $count->id,
                            createdBy: $command->postedBy,
                        ),
                    );
                    $line->adjustment_movement_id = $result['movement']->id;
                } else {
                    $result = $this->commands->dispatch(
                        new IssueStockCommand(
                            organizationId: $command->organizationId,
                            warehouseId: $count->warehouse_id,
                            productId: $line->product_id,
                            quantity: abs($variance),
                            reason: 'Stock count ' . $count->document_number,
                            documentType: 'STOCK_COUNT',
                            documentId: $count->id,
                            createdBy: $command->postedBy,
                        ),
                    );
                    $line->adjustment_movement_id = $result['movement']->id;
                }
                $line->save();
            }

            $count->status = 'POSTED';
            $count->posted_at = now();
            $count->save();

            return $count->fresh()->load('lines');
        });
    }
}
