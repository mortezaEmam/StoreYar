<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('supplier_id', 26);
            $table->string('warehouse_id', 26);
            $table->string('branch_id', 26)->nullable();
            $table->string('document_number', 64)->nullable();
            $table->string('status', 32)->default('DRAFT'); // DRAFT, ORDERED, PARTIAL, RECEIVED, CANCELLED
            $table->string('currency', 3)->default('IRR');
            $table->unsignedBigInteger('subtotal_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('expected_at')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('supplier_id');
            $table->index('warehouse_id');
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('purchase_order_id', 26);
            $table->string('product_id', 26);
            $table->unsignedInteger('quantity_ordered');
            $table->unsignedInteger('quantity_received')->default(0);
            $table->unsignedBigInteger('unit_cost_amount')->default(0);
            $table->unsignedBigInteger('line_total_amount')->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('product_id');
        });

        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('purchase_order_id', 26);
            $table->string('warehouse_id', 26);
            $table->string('document_number', 64)->nullable();
            $table->string('status', 32)->default('POSTED'); // POSTED, CANCELLED
            $table->timestamp('received_at');
            $table->string('received_by', 26)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'purchase_order_id']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('goods_receipt_id', 26);
            $table->string('purchase_order_line_id', 26);
            $table->string('product_id', 26);
            $table->unsignedInteger('quantity');
            $table->string('stock_movement_id', 26)->nullable();
            $table->timestamps();

            $table->index('goods_receipt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
