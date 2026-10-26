<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\RecordCustomerPayment;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryCommand;
use StoreYar\Modules\Accounting\Application\Services\AccountLookup;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\CustomerModel;
use StoreYar\Modules\Payments\Infrastructure\Persistence\Eloquent\PartyPaymentModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class RecordCustomerPaymentHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
        private ?AccountLookup $accounts = null,
    ) {}

    public function handle(Command $command): PartyPaymentModel
    {
        if (! $command instanceof RecordCustomerPaymentCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }
        if ($command->amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }

        $customer = CustomerModel::query()
            ->where('id', $command->customerId)
            ->where('organization_id', $command->organizationId)
            ->first();
        if ($customer === null) {
            throw new \DomainException('Customer not found.');
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $customer, $ids): PartyPaymentModel {
            $doc = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $doc = $gen->next($command->organizationId, 'CUSTOMER_PAYMENT', 'RCP-');
            } catch (\Throwable) {
                $doc = 'RCP-' . substr((string) new Ulid(), -8);
            }

            $payment = new PartyPaymentModel();
            $payment->id = $ids->generate();
            $payment->organization_id = $command->organizationId;
            $payment->party_type = 'CUSTOMER';
            $payment->party_id = $customer->id;
            $payment->direction = 'IN';
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

            // Reduce outstanding balance (min 0)
            $bal = max(0, (int) $customer->outstanding_balance_amount - $command->amount);
            $customer->outstanding_balance_amount = $bal;
            $customer->save();

            if ($command->postAccounting) {
                try {
                    $accounts = $this->accounts ?? app(AccountLookup::class);
                    $debit = strtoupper($command->method) === 'CASH'
                        ? $accounts->bySubtype($command->organizationId, 'CASH')
                        : $accounts->bySubtype($command->organizationId, 'BANK');
                    $ar = $accounts->bySubtype($command->organizationId, 'AR');

                    $je = $this->commands->dispatch(
                        new CreateJournalEntryCommand(
                            organizationId: $command->organizationId,
                            entryDate: now()->toDateString(),
                            lines: [
                                ['account_id' => $debit->id, 'debit_amount' => $command->amount, 'credit_amount' => 0, 'memo' => 'Customer payment'],
                                ['account_id' => $ar->id, 'debit_amount' => 0, 'credit_amount' => $command->amount, 'memo' => 'AR settlement'],
                            ],
                            memo: 'Customer payment ' . $doc,
                            sourceType: 'CUSTOMER_PAYMENT',
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
