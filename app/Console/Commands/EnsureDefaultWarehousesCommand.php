<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent\OrganizationModel;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

/**
 * Creates a default MAIN warehouse for every organization that does not have one yet.
 * Also backfills inventory_stock_items.warehouse_id where null.
 *
 * Run: php artisan storeyar:ensure-default-warehouses
 */
final class EnsureDefaultWarehousesCommand extends Command
{
    protected $signature = 'storeyar:ensure-default-warehouses {--dry-run : Show what would be done without writing}';

    protected $description = 'Ensure every organization has a default MAIN warehouse and backfill stock items';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $ids = new UlidGenerator();

        $organizations = OrganizationModel::query()->get(['id', 'name']);
        $this->info('Organizations found: ' . $organizations->count());

        $created = 0;
        $backfilled = 0;

        foreach ($organizations as $org) {
            $existing = WarehouseModel::query()
                ->where('organization_id', $org->id)
                ->where('code', 'MAIN')
                ->first();

            if ($existing === null) {
                if ($dryRun) {
                    $this->line("[dry-run] Would create MAIN warehouse for org {$org->id} ({$org->name})");
                } else {
                    $warehouse = new WarehouseModel();
                    $warehouse->id = $ids->generate();
                    $warehouse->organization_id = $org->id;
                    $warehouse->branch_id = null;
                    $warehouse->code = 'MAIN';
                    $warehouse->name = 'انبار اصلی';
                    $warehouse->type = 'MAIN';
                    $warehouse->is_active = true;
                    $warehouse->save();
                    $existing = $warehouse;
                    $created++;
                    $this->info("Created MAIN warehouse for org {$org->id}");
                }
            }

            if ($existing !== null && ! $dryRun) {
                $updated = DB::table('inventory_stock_items')
                    ->where('organization_id', $org->id)
                    ->whereNull('warehouse_id')
                    ->update(['warehouse_id' => $existing->id]);

                if ($updated > 0) {
                    $backfilled += $updated;
                    $this->info("Backfilled {$updated} stock items for org {$org->id}");
                }
            }
        }

        $this->info("Done. Warehouses created: {$created}, stock rows backfilled: {$backfilled}");

        return self::SUCCESS;
    }
}
