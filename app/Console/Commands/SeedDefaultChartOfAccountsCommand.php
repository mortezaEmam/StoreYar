<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\AccountModel;
use StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent\OrganizationModel;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

/**
 * php artisan storeyar:seed-chart-of-accounts
 * php artisan storeyar:seed-chart-of-accounts --organization=01H...
 */
final class SeedDefaultChartOfAccountsCommand extends Command
{
    protected $signature = 'storeyar:seed-chart-of-accounts {--organization= : Specific organization ULID}';

    protected $description = 'Seed a minimal default chart of accounts per organization';

    /** @var list<array{code: string, name: string, type: string, subtype: ?string}> */
    private array $defaults = [
        ['code' => '1000', 'name' => 'Cash', 'type' => 'ASSET', 'subtype' => 'CASH'],
        ['code' => '1100', 'name' => 'Bank', 'type' => 'ASSET', 'subtype' => 'BANK'],
        ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'ASSET', 'subtype' => 'AR'],
        ['code' => '1300', 'name' => 'Inventory', 'type' => 'ASSET', 'subtype' => 'INVENTORY'],
        ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'LIABILITY', 'subtype' => 'AP'],
        ['code' => '3000', 'name' => 'Owner Equity', 'type' => 'EQUITY', 'subtype' => 'EQUITY'],
        ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'REVENUE', 'subtype' => 'SALES'],
        ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'EXPENSE', 'subtype' => 'COGS'],
        ['code' => '5100', 'name' => 'Operating Expenses', 'type' => 'EXPENSE', 'subtype' => 'OPEX'],
    ];

    public function handle(): int
    {
        $ids = new UlidGenerator();
        $orgQuery = OrganizationModel::query();
        if ($this->option('organization')) {
            $orgQuery->where('id', $this->option('organization'));
        }

        $orgs = $orgQuery->get(['id', 'name']);
        $this->info('Organizations: ' . $orgs->count());

        foreach ($orgs as $org) {
            $created = 0;
            foreach ($this->defaults as $def) {
                $exists = AccountModel::query()
                    ->where('organization_id', $org->id)
                    ->where('code', $def['code'])
                    ->exists();
                if ($exists) {
                    continue;
                }
                $a = new AccountModel();
                $a->id = $ids->generate();
                $a->organization_id = $org->id;
                $a->code = $def['code'];
                $a->name = $def['name'];
                $a->type = $def['type'];
                $a->subtype = $def['subtype'];
                $a->is_system = true;
                $a->is_active = true;
                $a->save();
                $created++;
            }
            $this->info("Org {$org->id}: created {$created} accounts");
        }

        return self::SUCCESS;
    }
}
