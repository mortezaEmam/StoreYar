<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_returns', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('pos_sale_id', 26);
            $table->string('warehouse_id', 26);
            $table->string('document_number', 64)->nullable();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->string('status', 16)->default('COMPLETED');
            $table->string('reason')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->timestamp('returned_at');
            $table->timestamps();

            $table->index(['organization_id', 'pos_sale_id']);
        });

        Schema::create('pos_return_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('pos_return_id', 26);
            $table->string('pos_sale_line_id', 26)->nullable();
            $table->string('product_id', 26);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_amount')->default(0);
            $table->unsignedBigInteger('line_total_amount')->default(0);
            $table->string('stock_movement_id', 26)->nullable();
            $table->timestamps();

            $table->index('pos_return_id');
        });

        Schema::create('inventory_stock_transfers', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('from_warehouse_id', 26);
            $table->string('to_warehouse_id', 26);
            $table->string('document_number', 64)->nullable();
            $table->string('status', 16)->default('POSTED');
            $table->string('notes')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();

            $table->index('organization_id');
        });

        Schema::create('inventory_stock_transfer_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('stock_transfer_id', 26);
            $table->string('product_id', 26);
            $table->unsignedInteger('quantity');
            $table->string('out_movement_id', 26)->nullable();
            $table->string('in_movement_id', 26)->nullable();
            $table->timestamps();

            $table->index('stock_transfer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_transfer_lines');
        Schema::dropIfExists('inventory_stock_transfers');
        Schema::dropIfExists('pos_return_lines');
        Schema::dropIfExists('pos_returns');
    }
};
