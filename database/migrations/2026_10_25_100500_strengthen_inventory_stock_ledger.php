<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align inventory_stock_movements toward a proper stock ledger.
 * Additive. Short index names for MySQL 64-char limit.
 * Idempotent-ish for partial failure recovery.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = 'inventory_stock_movements';

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (! Schema::hasColumn($table, 'warehouse_id')) {
                $blueprint->string('warehouse_id', 26)->nullable()->after('organization_id');
            }
            if (! Schema::hasColumn($table, 'location_id')) {
                $blueprint->string('location_id', 26)->nullable();
            }
            if (! Schema::hasColumn($table, 'variant_id')) {
                $blueprint->string('variant_id', 26)->nullable();
            }
            if (! Schema::hasColumn($table, 'document_type')) {
                $blueprint->string('document_type', 64)->nullable();
            }
            if (! Schema::hasColumn($table, 'document_id')) {
                $blueprint->string('document_id', 26)->nullable();
            }
            if (! Schema::hasColumn($table, 'document_line_id')) {
                $blueprint->string('document_line_id', 26)->nullable();
            }
            if (! Schema::hasColumn($table, 'direction')) {
                $blueprint->string('direction', 8)->nullable();
            }
            if (! Schema::hasColumn($table, 'unit_id')) {
                $blueprint->string('unit_id', 26)->nullable();
            }
            if (! Schema::hasColumn($table, 'base_quantity')) {
                $blueprint->decimal('base_quantity', 18, 4)->nullable();
            }
            if (! Schema::hasColumn($table, 'unit_cost_amount')) {
                $blueprint->unsignedBigInteger('unit_cost_amount')->nullable();
            }
            if (! Schema::hasColumn($table, 'currency')) {
                $blueprint->string('currency', 3)->nullable();
            }
            if (! Schema::hasColumn($table, 'reason')) {
                $blueprint->string('reason')->nullable();
            }
            if (! Schema::hasColumn($table, 'created_by')) {
                $blueprint->string('created_by', 26)->nullable();
            }
        });

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->index(
                ['organization_id', 'warehouse_id', 'product_id'],
                'ism_org_wh_product_idx'
            );
            $blueprint->index(
                ['document_type', 'document_id'],
                'ism_doc_type_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_movements', function (Blueprint $table): void {
            $table->dropIndex('ism_org_wh_product_idx');
            $table->dropIndex('ism_doc_type_id_idx');
            $table->dropColumn([
                'warehouse_id',
                'location_id',
                'variant_id',
                'document_type',
                'document_id',
                'document_line_id',
                'direction',
                'unit_id',
                'base_quantity',
                'unit_cost_amount',
                'currency',
                'reason',
                'created_by',
            ]);
        });
    }
};
