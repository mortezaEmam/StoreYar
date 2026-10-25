<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('brand_id', 26)->nullable()->after('category_id');
            $table->string('product_type', 32)->default('simple')->after('brand_id');
            $table->string('base_unit_id', 26)->nullable()->after('product_type');
            $table->string('sale_unit_id', 26)->nullable()->after('base_unit_id');
            $table->string('purchase_unit_id', 26)->nullable()->after('sale_unit_id');
            $table->string('short_name')->nullable()->after('name');
            $table->text('description')->nullable()->after('short_name');
            $table->boolean('is_sellable')->default(true)->after('status');
            $table->boolean('is_purchasable')->default(true)->after('is_sellable');
            $table->boolean('is_stockable')->default(true)->after('is_purchasable');
            $table->boolean('is_taxable')->default(true)->after('is_stockable');
            $table->unsignedBigInteger('cost_price_amount')->nullable()->after('unit_price_amount');
            $table->decimal('min_stock', 18, 4)->nullable()->after('cost_price_amount');
            $table->decimal('max_stock', 18, 4)->nullable()->after('min_stock');
            $table->decimal('reorder_point', 18, 4)->nullable()->after('max_stock');
            $table->decimal('reorder_qty', 18, 4)->nullable()->after('reorder_point');
            $table->boolean('track_lot')->default(false)->after('reorder_qty');
            $table->boolean('track_serial')->default(false)->after('track_lot');
            $table->boolean('track_expiry')->default(false)->after('track_serial');
            $table->timestamp('archived_at')->nullable()->after('track_expiry');

            $table->index('brand_id');
            $table->index('base_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropIndex(['brand_id']);
            $table->dropIndex(['base_unit_id']);
            $table->dropColumn([
                'brand_id',
                'product_type',
                'base_unit_id',
                'sale_unit_id',
                'purchase_unit_id',
                'short_name',
                'description',
                'is_sellable',
                'is_purchasable',
                'is_stockable',
                'is_taxable',
                'cost_price_amount',
                'min_stock',
                'max_stock',
                'reorder_point',
                'reorder_qty',
                'track_lot',
                'track_serial',
                'track_expiry',
                'archived_at',
            ]);
        });
    }
};
