<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Commands\PostJournalEntry;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class PostJournalEntryCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $journalEntryId,
        public ?string $postedBy = null,
    ) {}
}
