<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Commands\OpeningBalance;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class OpeningBalanceCommand implements Command
{
    /**
     * @param list<array{account_id: string, debit_amount?: int, credit_amount?: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $asOfDate,
        public array $lines,
        public ?string $notes = null,
        public ?string $createdBy = null,
    ) {}
}
