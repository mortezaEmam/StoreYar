<?php

declare(strict_types=1);

namespace Tests\Feature\StoreYar;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Accounting\Application\Commands\CreateAccount\CreateAccountCommand;
use StoreYar\Modules\Accounting\Application\Commands\CreateJournalEntry\CreateJournalEntryCommand;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

/**
 * Double-entry must balance.
 * Run: php artisan test --filter=JournalBalanceTest
 */
final class JournalBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unbalanced_journal_is_rejected(): void
    {
        $commands = $this->app->make(CommandBus::class);
        $orgId = $this->bootstrapOrg($commands);

        $cash = $commands->dispatch(new CreateAccountCommand(
            organizationId: $orgId,
            code: '1000',
            name: 'Cash',
            type: 'ASSET',
            subtype: 'CASH',
        ));
        $sales = $commands->dispatch(new CreateAccountCommand(
            organizationId: $orgId,
            code: '4000',
            name: 'Sales',
            type: 'REVENUE',
            subtype: 'SALES',
        ));

        $this->expectException(\DomainException::class);

        $commands->dispatch(new CreateJournalEntryCommand(
            organizationId: $orgId,
            entryDate: now()->toDateString(),
            lines: [
                ['account_id' => $cash->id, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => $sales->id, 'debit_amount' => 0, 'credit_amount' => 50],
            ],
            post: true,
        ));
    }

    public function test_balanced_journal_posts(): void
    {
        $commands = $this->app->make(CommandBus::class);
        $orgId = $this->bootstrapOrg($commands);

        $cash = $commands->dispatch(new CreateAccountCommand(
            organizationId: $orgId,
            code: '1000',
            name: 'Cash',
            type: 'ASSET',
            subtype: 'CASH',
        ));
        $sales = $commands->dispatch(new CreateAccountCommand(
            organizationId: $orgId,
            code: '4000',
            name: 'Sales',
            type: 'REVENUE',
            subtype: 'SALES',
        ));

        $je = $commands->dispatch(new CreateJournalEntryCommand(
            organizationId: $orgId,
            entryDate: now()->toDateString(),
            lines: [
                ['account_id' => $cash->id, 'debit_amount' => 1000, 'credit_amount' => 0],
                ['account_id' => $sales->id, 'debit_amount' => 0, 'credit_amount' => 1000],
            ],
            post: true,
        ));

        $this->assertSame('POSTED', $je->status);
        $this->assertSame(1000, (int) $je->total_debit);
        $this->assertSame(1000, (int) $je->total_credit);
    }

    private function bootstrapOrg(CommandBus $commands): string
    {
        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'acct-test@example.com', name: 'Acct Tester'),
        );
        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $org = $commands->dispatch(
            new CreateOrganizationCommand(
                name: 'Acct Test Org',
                ownerUserId: (string) $user->id(),
            ),
        );

        if (is_object($org) && method_exists($org, 'organizationId')) {
            return $org->organizationId()->value();
        }

        return (string) DB::table('organization_organizations')->orderByDesc('created_at')->value('id');
    }
}
