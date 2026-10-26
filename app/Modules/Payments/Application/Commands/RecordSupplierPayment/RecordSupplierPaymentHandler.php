<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\RecordSupplierPayment;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryCommand;
use StoreYar\Modules\Accounting\Application\Services\AccountLookup;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\SupplierModel;
use StoreYar\Modules\Payments\Infrastructure\Persistence\Eloquent\PartyPaymentModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class RecordSupplierPaymentHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
        private ?AccountLookup $accounts = null,
    ) {}

    public function handle(Command $command): PartyPaymentModel
    {
        if (! $command instanceof RecordSupplierPaymentCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }
        if ($command->amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }

        $supplier = SupplierModel::query()
            ->where('id', $command->supplierId)
            ->where('organization_id', $command->organizationId)
            ->first();
        if ($supplier === null) {
            throw new \DomainException('Supplier not found.');
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $supplier, $ids): PartyPaymentModel {
            $doc = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $doc = $gen->next($command->organizationId, 'SUPPLIER_PAYMENT', 'PAY-');
            } catch (\Throwable) {
                $doc = 'PAY-' . substr((string) new Ulid(), -8);
            }

            $payment = new PartyPaymentModel();
            $payment->id = $ids->generate();
            $payment->organization_id = $command->organizationId;
            $payment->party_type = 'SUPPLIER';
            $payment->party_id = $supplier->id;
            $payment->direction = 'OUT';
            $payment->amount = $command->amount;
            $payment->currency = strtoupper($command->currency);
            $payment->method = strtoupper($command->method);
            $payment->document_number = $doc;
            $payment->reference = $command->reference;
            $payment->status = 'POSTED';
            $payment->notes = $command->notes;
            $payment->created_by = $command->createdBy;
            $payment->paid_at = now();
            $payment->save();

            $bal = max(0, (int) $supplier->outstanding_balance_amount - $command->amount);
            $supplier->outstanding_balance_amount = $bal;
            $supplier->save();

            if ($command->postAccounting) {
                try {
                    $accounts = $this->accounts ?? app(AccountLookup::class);
                    $credit = strtoupper($command->method) === 'CASH'
                        ? $accounts->bySubtype($command->organizationId, 'CASH')
                        : $accounts->bySubtype($command->organizationId, 'BANK');
                    $ap = $accounts->bySubtype($command->organizationId, 'AP');

                    $je = $this->commands->dispatch(
                        new CreateJournalEntryCommand(
                            organizationId: $command->organizationId,
                            entryDate: now()->toDateString(),
                            lines: [
                                ['account_id' => $ap->id, 'debit_amount' => $command->amount, 'credit_amount' => 0, 'memo' => 'AP settlement'],
                                ['account_id' => $credit->id, 'debit_amount' => 0, 'credit_amount' => $command->amount, 'memo' => 'Supplier payment'],
                            ],
                            memo: 'Supplier payment ' . $doc,
                            sourceType: 'SUPPLIER_PAYMENT',
                            sourceId: $payment->id,
                            post: true,
                        ),
                    );
                    $payment->journal_entry_id = $je->id;
                    $payment->save();
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $payment;
        });
    }
}
