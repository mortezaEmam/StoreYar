<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

/**
 * Seeds a minimal demo catalog + stock for an organization.
 *
 * php artisan storeyar:seed-demo-retail {organization_id}
 * php artisan storeyar:seed-demo-retail {organization_id} --warehouse=MAIN
 */
final class SeedDemoRetailCommand extends Command
{
    protected $signature = 'storeyar:seed-demo-retail
        {organization_id : Organization / business ULID}
        {--warehouse=MAIN : Warehouse code to use or create}';

    protected $description = 'Seed demo products, barcodes and opening stock for POS testing';

    public function handle(): int
    {
        $orgId = (string) $this->argument('organization_id');
        $whCode = (string) $this->option('warehouse');
        $ids = new UlidGenerator();
        $now = now();

        if (! Schema::hasTable('catalog_products')) {
            $this->error('catalog_products missing — run migrations first.');

            return self::FAILURE;
        }

        $warehouseId = DB::table('inventory_warehouses')
            ->where('organization_id', $orgId)
            ->where('code', $whCode)
            ->value('id');

        if (! $warehouseId && Schema::hasTable('inventory_warehouses')) {
            $warehouseId = $ids->generate();
            DB::table('inventory_warehouses')->insert([
                'id' => $warehouseId,
                'organization_id' => $orgId,
                'code' => $whCode,
                'name' => 'Main Warehouse',
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->info("Created warehouse {$whCode}: {$warehouseId}");
        }

        if (! $warehouseId) {
            $this->error('No warehouse available.');

            return self::FAILURE;
        }

        $demos = [
            ['sku' => 'DEMO-TEA', 'name' => 'Demo Tea 500g', 'barcode' => '120', 'price' => 85000, 'cost' => 50000, 'qty' => 50],
            ['sku' => 'DEMO-RICE', 'name' => 'Demo Rice 1kg', 'barcode' => '121', 'price' => 120000, 'cost' => 90000, 'qty' => 30],
            ['sku' => 'DEMO-WATER', 'name' => 'Demo Water 1.5L', 'barcode' => '122', 'price' => 15000, 'cost' => 8000, 'qty' => 100],
        ];

        foreach ($demos as $d) {
            $productId = DB::table('catalog_products')
                ->where('organization_id', $orgId)
                ->where('sku', $d['sku'])
                ->value('id');

            if (! $productId) {
                $productId = $ids->generate();
                $row = [
                    'id' => $productId,
                    'organization_id' => $orgId,
                    'sku' => $d['sku'],
                    'name' => $d['name'],
                    'status' => 'active',
                    'unit_price_amount' => $d['price'],
                    'currency' => 'IRR',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('catalog_products', 'cost_price_amount')) {
                    $row['cost_price_amount'] = $d['cost'];
                }
                if (Schema::hasColumn('catalog_products', 'is_sellable')) {
                    $row['is_sellable'] = true;
                    $row['is_stockable'] = true;
                }
                DB::table('catalog_products')->insert($row);
                $this->info("Product {$d['sku']}: {$productId}");
            } else {
                $this->line("Product {$d['sku']} exists: {$productId}");
            }

            if (Schema::hasTable('catalog_product_barcodes')) {
                $exists = DB::table('catalog_product_barcodes')
                    ->where('organization_id', $orgId)
                    ->where('barcode', $d['barcode'])
                    ->exists();
                if (! $exists) {
                    $bc = [
                        'id' => $ids->generate(),
                        'organization_id' => $orgId,
                        'product_id' => $productId,
                        'barcode' => $d['barcode'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (Schema::hasColumn('catalog_product_barcodes', 'type')) {
                        $bc['type'] = 'INTERNAL';
                    }
                    if (Schema::hasColumn('catalog_product_barcodes', 'is_primary')) {
                        $bc['is_primary'] = true;
                    }
                    DB::table('catalog_product_barcodes')->insert($bc);
                    $this->info("  barcode {$d['barcode']}");
                }
            }

            if (Schema::hasTable('inventory_stock_items')) {
                $stock = DB::table('inventory_stock_items')
                    ->where('organization_id', $orgId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->first();

                if (! $stock) {
                    $stockId = $ids->generate();
                    $stockRow = [
                        'id' => $stockId,
                        'organization_id' => $orgId,
                        'warehouse_id' => $warehouseId,
                        'product_id' => $productId,
                        'quantity' => $d['qty'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (Schema::hasColumn('inventory_stock_items', 'reserved_quantity')) {
                        $stockRow['reserved_quantity'] = 0;
                    }
                    if (Schema::hasColumn('inventory_stock_items', 'version')) {
                        $stockRow['version'] = 1;
                    }
                    DB::table('inventory_stock_items')->insert($stockRow);

                    if (Schema::hasTable('inventory_stock_movements')) {
                        $mov = [
                            'id' => $ids->generate(),
                            'organization_id' => $orgId,
                            'warehouse_id' => $warehouseId,
                            'product_id' => $productId,
                            'quantity_delta' => $d['qty'],
                            'quantity_after' => $d['qty'],
                            'direction' => 'IN',
                            'reason' => 'Demo seed',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        if (Schema::hasColumn('inventory_stock_movements', 'movement_type')) {
                            $mov['movement_type'] = 'RECEIVE';
                        }
                        if (Schema::hasColumn('inventory_stock_movements', 'document_type')) {
                            $mov['document_type'] = 'OPENING_STOCK';
                        }
                        if (Schema::hasColumn('inventory_stock_movements', 'occurred_at')) {
                            $mov['occurred_at'] = $now;
                        }
                        DB::table('inventory_stock_movements')->insert($mov);
                    }
                    $this->info("  stock qty {$d['qty']}");
                }
            }
        }

        $this->newLine();
        $this->info('Demo ready. POS barcodes: 120, 121, 122');
        $this->info("Warehouse id: {$warehouseId}");

        return self::SUCCESS;
    }
}
