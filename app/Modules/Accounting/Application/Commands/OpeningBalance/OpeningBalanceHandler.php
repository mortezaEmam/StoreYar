<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Commands\OpeningBalance;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryCommand;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class OpeningBalanceHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commands,
        private ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function handle(Command $command): array
    {
        if (! $command instanceof OpeningBalanceCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }
        if (count($command->lines) < 2) {
            throw new \InvalidArgumentException('Opening balance needs at least two lines.');
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $ids): array {
            $doc = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $doc = $gen->next($command->organizationId, 'OPENING_BALANCE', 'OB-');
            } catch (\Throwable) {
                $doc = 'OB-' . substr((string) new Ulid(), -8);
            }

            $headerId = $ids->generate();
            $now = now();

            $je = $this->commands->dispatch(
                new CreateJournalEntryCommand(
                    organizationId: $command->organizationId,
                    entryDate: $command->asOfDate,
                    lines: $command->lines,
                    memo: 'Opening balances ' . $doc,
                    sourceType: 'OPENING_BALANCE',
                    sourceId: $headerId,
                    createdBy: $command->createdBy,
                    post: true,
                ),
            );

            DB::table('accounting_opening_balances')->insert([
                'id' => $headerId,
                'organization_id' => $command->organizationId,
                'document_number' => $doc,
                'status' => 'POSTED',
                'as_of_date' => $command->asOfDate,
                'journal_entry_id' => $je->id,
                'notes' => $command->notes,
                'created_by' => $command->createdBy,
                'posted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($command->lines as $line) {
                DB::table('accounting_opening_balance_lines')->insert([
                    'id' => (string) new Ulid(),
                    'opening_balance_id' => $headerId,
                    'account_id' => $line['account_id'],
                    'debit_amount' => (int) ($line['debit_amount'] ?? 0),
                    'credit_amount' => (int) ($line['credit_amount'] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return [
                'id' => $headerId,
                'document_number' => $doc,
                'journal_entry_id' => $je->id,
                'as_of_date' => $command->asOfDate,
                'total_debit' => $je->total_debit,
                'total_credit' => $je->total_credit,
            ];
        });
    }
}
