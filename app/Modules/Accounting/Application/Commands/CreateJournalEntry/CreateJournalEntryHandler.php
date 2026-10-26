<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\AccountModel;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\FiscalYearModel;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\JournalEntryModel;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\JournalLineModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Application\Document\DocumentNumberGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Symfony\Component\Uid\Ulid;

final class CreateJournalEntryHandler implements CommandHandler
{
    public function __construct(
        private ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function handle(Command $command): JournalEntryModel
    {
        if (! $command instanceof CreateJournalEntryCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        if (count($command->lines) < 2) {
            throw new \InvalidArgumentException('Journal entry requires at least two lines.');
        }

        $ids = new UlidGenerator();

        return DB::transaction(function () use ($command, $ids): JournalEntryModel {
            $totalDebit = 0;
            $totalCredit = 0;
            $resolved = [];
            $lineNo = 1;

            foreach ($command->lines as $line) {
                $accountId = $line['account_id'] ?? null;
                $debit = (int) ($line['debit_amount'] ?? 0);
                $credit = (int) ($line['credit_amount'] ?? 0);

                if (! is_string($accountId) || $accountId === '') {
                    throw new \InvalidArgumentException('account_id required on each line.');
                }
                if ($debit < 0 || $credit < 0) {
                    throw new \InvalidArgumentException('Amounts cannot be negative.');
                }
                if (($debit > 0 && $credit > 0) || ($debit === 0 && $credit === 0)) {
                    throw new \InvalidArgumentException('Each line must have either debit or credit (not both, not neither).');
                }

                $account = AccountModel::query()
                    ->where('id', $accountId)
                    ->where('organization_id', $command->organizationId)
                    ->where('is_active', true)
                    ->first();
                if ($account === null) {
                    throw new \DomainException("Account not found: {$accountId}");
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
                $resolved[] = [
                    'account_id' => $accountId,
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'memo' => $line['memo'] ?? null,
                    'line_no' => $lineNo++,
                ];
            }

            if ($totalDebit !== $totalCredit) {
                throw new \DomainException(
                    "Journal not balanced. Debit={$totalDebit}, Credit={$totalCredit}"
                );
            }
            if ($totalDebit === 0) {
                throw new \DomainException('Journal totals cannot be zero.');
            }

            $fiscalYearId = FiscalYearModel::query()
                ->where('organization_id', $command->organizationId)
                ->where('is_current', true)
                ->where('status', 'OPEN')
                ->value('id');

            $docNumber = null;
            try {
                $gen = $this->numbers ?? app(DocumentNumberGenerator::class);
                $docNumber = $gen->next(
                    organizationId: $command->organizationId,
                    documentType: 'JOURNAL',
                    prefix: 'JE-',
                );
            } catch (\Throwable) {
                $docNumber = 'JE-' . substr((string) new Ulid(), -8);
            }

            $entry = new JournalEntryModel();
            $entry->id = $ids->generate();
            $entry->organization_id = $command->organizationId;
            $entry->fiscal_year_id = $fiscalYearId;
            $entry->document_number = $docNumber;
            $entry->status = $command->post ? 'POSTED' : 'DRAFT';
            $entry->entry_date = $command->entryDate;
            $entry->source_type = $command->sourceType;
            $entry->source_id = $command->sourceId;
            $entry->memo = $command->memo;
            $entry->currency = strtoupper($command->currency);
            $entry->total_debit = $totalDebit;
            $entry->total_credit = $totalCredit;
            $entry->created_by = $command->createdBy;
            $entry->version = 0;

            if ($command->post) {
                $entry->posted_at = now();
                $entry->posted_by = $command->createdBy;
            }

            $entry->save();

            foreach ($resolved as $r) {
                $jl = new JournalLineModel();
                $jl->id = (string) new Ulid();
                $jl->journal_entry_id = $entry->id;
                $jl->account_id = $r['account_id'];
                $jl->debit_amount = $r['debit_amount'];
                $jl->credit_amount = $r['credit_amount'];
                $jl->currency = $entry->currency;
                $jl->memo = $r['memo'];
                $jl->line_no = $r['line_no'];
                $jl->save();
            }

            return $entry->load('lines');
        });
    }
}
