<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\OpenPosSession;

use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSessionModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class OpenPosSessionHandler implements CommandHandler
{
    public function handle(Command $command): PosSessionModel
    {
        if (! $command instanceof OpenPosSessionCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $wh = WarehouseModel::query()
            ->where('id', $command->warehouseId)
            ->where('organization_id', $command->organizationId)
            ->where('is_active', true)
            ->first();

        if ($wh === null) {
            throw new \DomainException('Warehouse not found or inactive.');
        }

        $open = PosSessionModel::query()
            ->where('organization_id', $command->organizationId)
            ->where('register_code', $command->registerCode)
            ->where('status', 'OPEN')
            ->exists();

        if ($open) {
            throw new \DomainException('An open POS session already exists for this register.');
        }

        $s = new PosSessionModel();
        $s->id = (new UlidGenerator())->generate();
        $s->organization_id = $command->organizationId;
        $s->branch_id = $command->branchId;
        $s->warehouse_id = $command->warehouseId;
        $s->register_code = $command->registerCode;
        $s->opened_by = $command->openedBy;
        $s->opened_at = now();
        $s->opening_float_amount = $command->openingFloatAmount;
        $s->currency = strtoupper($command->currency);
        $s->status = 'OPEN';
        $s->notes = $command->notes;
        $s->save();

        return $s;
    }
}
