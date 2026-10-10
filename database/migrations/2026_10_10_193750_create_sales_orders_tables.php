<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('customer_user_id', 26);
            $table->string('status', 32);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('customer_user_id');
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('order_id', 26);
            $table->string('product_id', 26);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_amount');
            $table->string('currency', 3);
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('sales_orders')
                ->cascadeOnDelete();

            $table->unique(['order_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
