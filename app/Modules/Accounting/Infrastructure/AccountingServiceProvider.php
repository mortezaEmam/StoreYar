<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Accounting\Application\Commands\CreateAccount\CreateAccountCommand;
use StoreYar\Modules\Accounting\Application\Commands\CreateAccount\CreateAccountHandler;
use StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryCommand;
use StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryHandler;
use StoreYar\Modules\Accounting\Application\Commands\PostJournalEntry\PostJournalEntryCommand;
use StoreYar\Modules\Accounting\Application\Commands\PostJournalEntry\PostJournalEntryHandler;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;

final class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $commands = $this->app->make(CommandHandlerRegistry::class);
        $commands->register(CreateAccountCommand::class, CreateAccountHandler::class);
        $commands->register(CreateJournalEntryCommand::class, CreateJournalEntryHandler::class);
        $commands->register(PostJournalEntryCommand::class, PostJournalEntryHandler::class);
    }
}
