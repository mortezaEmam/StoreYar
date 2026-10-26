<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Services;

use StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryCommand;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\JournalEntryModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;

/**
 * Posts double-entry journals from operational documents (POS, GRN).
 * Idempotent on source_type + source_id.
 */
final class OperationalPostingService
{
    public function __construct(
        private CommandBus $commands,
        private AccountLookup $accounts,
    ) {}

    public function postPosSale(
        string $organizationId,
        string $posSaleId,
        int $totalAmount,
        string $paymentMethod = 'CASH',
        string $entryDate = null,
    ): ?JournalEntryModel {
        if ($totalAmount <= 0) {
            return null;
        }

        if ($this->alreadyPosted($organizationId, 'POS_SALE', $posSaleId)) {
            return JournalEntryModel::query()
                ->where('organization_id', $organizationId)
                ->where('source_type', 'POS_SALE')
                ->where('source_id', $posSaleId)
                ->where('status', 'POSTED')
                ->first();
        }

        $cashOrBank = strtoupper($paymentMethod) === 'CARD'
            ? $this->accounts->bySubtype($organizationId, 'BANK')
            : $this->accounts->bySubtype($organizationId, 'CASH');

        if (strtoupper($paymentMethod) === 'CREDIT') {
            $cashOrBank = $this->accounts->bySubtype($organizationId, 'AR');
        }

        $sales = $this->accounts->bySubtype($organizationId, 'SALES');

        return $this->commands->dispatch(
            new CreateJournalEntryCommand(
                organizationId: $organizationId,
                entryDate: $entryDate ?? now()->toDateString(),
                lines: [
                    [
                        'account_id' => $cashOrBank->id,
                        'debit_amount' => $totalAmount,
                        'credit_amount' => 0,
                        'memo' => 'POS sale receipt',
                    ],
                    [
                        'account_id' => $sales->id,
                        'debit_amount' => 0,
                        'credit_amount' => $totalAmount,
                        'memo' => 'POS sale revenue',
                    ],
                ],
                memo: 'Auto post POS sale ' . $posSaleId,
                sourceType: 'POS_SALE',
                sourceId: $posSaleId,
                post: true,
            ),
        );
    }

    public function postGoodsReceipt(
        string $organizationId,
        string $goodsReceiptId,
        int $totalCostAmount,
        string $entryDate = null,
    ): ?JournalEntryModel {
        if ($totalCostAmount <= 0) {
            return null;
        }

        if ($this->alreadyPosted($organizationId, 'GOODS_RECEIPT', $goodsReceiptId)) {
            return JournalEntryModel::query()
                ->where('organization_id', $organizationId)
                ->where('source_type', 'GOODS_RECEIPT')
                ->where('source_id', $goodsReceiptId)
                ->where('status', 'POSTED')
                ->first();
        }

        $inventory = $this->accounts->bySubtype($organizationId, 'INVENTORY');
        $ap = $this->accounts->bySubtype($organizationId, 'AP');

        return $this->commands->dispatch(
            new CreateJournalEntryCommand(
                organizationId: $organizationId,
                entryDate: $entryDate ?? now()->toDateString(),
                lines: [
                    [
                        'account_id' => $inventory->id,
                        'debit_amount' => $totalCostAmount,
                        'credit_amount' => 0,
                        'memo' => 'Goods receipt inventory',
                    ],
                    [
                        'account_id' => $ap->id,
                        'debit_amount' => 0,
                        'credit_amount' => $totalCostAmount,
                        'memo' => 'Goods receipt AP',
                    ],
                ],
                memo: 'Auto post GRN ' . $goodsReceiptId,
                sourceType: 'GOODS_RECEIPT',
                sourceId: $goodsReceiptId,
                post: true,
            ),
        );
    }

    private function alreadyPosted(string $organizationId, string $sourceType, string $sourceId): bool
    {
        return JournalEntryModel::query()
            ->where('organization_id', $organizationId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereIn('status', ['POSTED', 'DRAFT'])
            ->exists();
    }
}
