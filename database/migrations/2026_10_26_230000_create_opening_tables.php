<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_opening_stocks', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('warehouse_id', 26);
            $table->string('document_number', 64)->nullable();
            $table->string('status', 16)->default('POSTED');
            $table->string('notes')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->index(['organization_id', 'warehouse_id']);
        });

        Schema::create('inventory_opening_stock_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('opening_stock_id', 26);
            $table->string('product_id', 26);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_cost_amount')->default(0);
            $table->string('stock_movement_id', 26)->nullable();
            $table->timestamps();

            $table->index('opening_stock_id');
        });

        Schema::create('accounting_opening_balances', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('document_number', 64)->nullable();
            $table->string('status', 16)->default('POSTED');
            $table->date('as_of_date');
            $table->string('journal_entry_id', 26)->nullable();
            $table->string('notes')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->index('organization_id');
        });

        Schema::create('accounting_opening_balance_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('opening_balance_id', 26);
            $table->string('account_id', 26);
            $table->unsignedBigInteger('debit_amount')->default(0);
            $table->unsignedBigInteger('credit_amount')->default(0);
            $table->timestamps();

            $table->index('opening_balance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_opening_balance_lines');
        Schema::dropIfExists('accounting_opening_balances');
        Schema::dropIfExists('inventory_opening_stock_lines');
        Schema::dropIfExists('inventory_opening_stocks');
    }
};
