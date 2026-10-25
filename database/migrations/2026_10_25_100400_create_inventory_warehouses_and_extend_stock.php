<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_warehouses', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('branch_id', 26)->nullable();
            $table->string('code', 32);
            $table->string('name');
            $table->string('type', 32)->default('MAIN'); // MAIN, STORE, TRANSIT, QUARANTINE
            $table->boolean('is_active')->default(true);
            $table->text('address')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
            $table->index('branch_id');
        });

        Schema::table('inventory_stock_items', function (Blueprint $table): void {
            $table->string('warehouse_id', 26)->nullable()->after('organization_id');
            $table->string('variant_id', 26)->nullable()->after('product_id');
            $table->string('location_id', 26)->nullable()->after('warehouse_id');

            $table->index('warehouse_id');
            $table->index('variant_id');
        });

        // Drop old unique and create new one that includes warehouse
        // Note: existing data will have null warehouse_id until backfill
        Schema::table('inventory_stock_items', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'product_id']);
            $table->unique(
                ['organization_id', 'warehouse_id', 'product_id', 'variant_id'],
                'inventory_stock_items_org_wh_product_variant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_items', function (Blueprint $table): void {
            $table->dropUnique('inventory_stock_items_org_wh_product_variant_unique');
            $table->unique(['organization_id', 'product_id']);
            $table->dropIndex(['warehouse_id']);
            $table->dropIndex(['variant_id']);
            $table->dropColumn(['warehouse_id', 'variant_id', 'location_id']);
        });

        Schema::dropIfExists('inventory_warehouses');
    }
};
