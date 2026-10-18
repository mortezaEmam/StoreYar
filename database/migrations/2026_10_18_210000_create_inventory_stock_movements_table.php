<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_movements', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('stock_item_id', 26);
            $table->string('product_id', 26);
            $table->string('movement_type', 32);
            $table->integer('quantity_delta');
            $table->integer('reserved_delta');
            $table->integer('quantity_after');
            $table->integer('reserved_after');
            $table->string('event_id', 26)->nullable()->unique();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at');

            $table->index(['organization_id', 'product_id']);
            $table->index(['stock_item_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
    }
};
