<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_stock_items', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('product_id', 26);
            $table->integer('quantity');
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'product_id']);
            $table->index('organization_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_items');
    }
};
