<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\ClosePosSession;

use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSaleModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSessionModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class ClosePosSessionHandler implements CommandHandler
{
    public function handle(Command $command): PosSessionModel
    {
        if (! $command instanceof ClosePosSessionCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $s = PosSessionModel::query()
            ->where('id', $command->sessionId)
            ->where('organization_id', $command->organizationId)
            ->first();

        if ($s === null) {
            throw new \DomainException('POS session not found.');
        }
        if ($s->status !== 'OPEN') {
            throw new \DomainException('POS session is not open.');
        }

        $cashSales = (int) PosSaleModel::query()
            ->where('pos_session_id', $s->id)
            ->where('status', 'COMPLETED')
            ->where('payment_method', 'CASH')
            ->sum('total_amount');

        $expected = (int) $s->opening_float_amount + $cashSales;
        $variance = $command->closingCountAmount - $expected;

        $s->closed_by = $command->closedBy;
        $s->closed_at = now();
        $s->closing_count_amount = $command->closingCountAmount;
        $s->variance_amount = $variance;
        $s->status = 'CLOSED';
        if ($command->notes !== null) {
            $s->notes = trim(($s->notes ? $s->notes . "\n" : '') . $command->notes);
        }
        $s->save();

        return $s;
    }
}
