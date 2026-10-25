<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('branch_id', 26)->nullable();
            $table->string('warehouse_id', 26);
            $table->string('register_code', 32)->default('POS1');
            $table->string('opened_by', 26)->nullable();
            $table->timestamp('opened_at');
            $table->unsignedBigInteger('opening_float_amount')->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->string('closed_by', 26)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closing_count_amount')->nullable();
            $table->bigInteger('variance_amount')->nullable();
            $table->string('status', 16)->default('OPEN'); // OPEN, CLOSED
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('warehouse_id');
        });

        Schema::create('pos_sales', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('pos_session_id', 26);
            $table->string('warehouse_id', 26);
            $table->string('customer_id', 26)->nullable();
            $table->string('document_number', 64)->nullable();
            $table->unsignedBigInteger('subtotal_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->string('payment_method', 32)->default('CASH'); // CASH, CARD, MIXED, CREDIT
            $table->string('status', 16)->default('COMPLETED'); // COMPLETED, VOIDED
            $table->string('created_by', 26)->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();

            $table->index(['organization_id', 'pos_session_id']);
            $table->index('warehouse_id');
        });

        Schema::create('pos_sale_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('pos_sale_id', 26);
            $table->string('product_id', 26);
            $table->string('barcode', 64)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_amount');
            $table->unsignedBigInteger('line_total_amount');
            $table->string('currency', 3)->default('IRR');
            $table->string('stock_movement_id', 26)->nullable();
            $table->timestamps();

            $table->index('pos_sale_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_lines');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('pos_sessions');
    }
};
