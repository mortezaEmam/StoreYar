<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateJournalEntryCommand implements Command
{
    /**
     * @param list<array{account_id: string, debit_amount?: int, credit_amount?: int, memo?: string}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $entryDate,
        public array $lines,
        public ?string $memo = null,
        public string $currency = 'IRR',
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        public ?string $createdBy = null,
        public bool $post = false,
    ) {}
}
