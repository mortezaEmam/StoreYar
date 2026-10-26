<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('purchase_order_id', 26)->nullable();
            $table->string('supplier_id', 26);
            $table->string('warehouse_id', 26);
            $table->string('document_number', 64)->nullable();
            $table->string('status', 16)->default('POSTED');
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->string('reason')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->timestamp('returned_at');
            $table->timestamps();

            $table->index(['organization_id', 'supplier_id']);
            $table->index('purchase_order_id');
        });

        Schema::create('purchase_return_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('purchase_return_id', 26);
            $table->string('product_id', 26);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_cost_amount')->default(0);
            $table->unsignedBigInteger('line_total_amount')->default(0);
            $table->string('stock_movement_id', 26)->nullable();
            $table->timestamps();

            $table->index('purchase_return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
    }
};
