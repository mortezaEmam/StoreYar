<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_counts', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('warehouse_id', 26);
            $table->string('document_number', 64)->nullable();
            $table->string('status', 16)->default('DRAFT'); // DRAFT, POSTED, CANCELLED
            $table->string('notes')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'warehouse_id']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('inventory_stock_count_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('stock_count_id', 26);
            $table->string('product_id', 26);
            $table->unsignedInteger('system_quantity')->default(0);
            $table->unsignedInteger('counted_quantity')->default(0);
            $table->integer('variance_quantity')->default(0); // counted - system
            $table->string('adjustment_movement_id', 26)->nullable();
            $table->timestamps();

            $table->index('stock_count_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_count_lines');
        Schema::dropIfExists('inventory_stock_counts');
    }
};
