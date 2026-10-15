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
        Schema::create('payments_payments', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('order_id', 26);
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);
            $table->string('status', 32);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
            $table->index(['organization_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments_payments');
    }
};
