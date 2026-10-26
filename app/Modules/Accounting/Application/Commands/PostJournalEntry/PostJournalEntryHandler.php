<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Commands\PostJournalEntry;

use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\JournalEntryModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class PostJournalEntryHandler implements CommandHandler
{
    public function handle(Command $command): JournalEntryModel
    {
        if (! $command instanceof PostJournalEntryCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $entry = JournalEntryModel::query()
            ->where('id', $command->journalEntryId)
            ->where('organization_id', $command->organizationId)
            ->with('lines')
            ->first();

        if ($entry === null) {
            throw new \DomainException('Journal entry not found.');
        }
        if ($entry->status !== 'DRAFT') {
            throw new \DomainException("Cannot post entry in status: {$entry->status}");
        }
        if ((int) $entry->total_debit !== (int) $entry->total_credit) {
            throw new \DomainException('Journal is not balanced.');
        }
        if ($entry->lines->count() < 2) {
            throw new \DomainException('Journal must have at least two lines.');
        }

        $entry->status = 'POSTED';
        $entry->posted_at = now();
        $entry->posted_by = $command->postedBy;
        $entry->version = (int) $entry->version + 1;
        $entry->save();

        return $entry;
    }
}
