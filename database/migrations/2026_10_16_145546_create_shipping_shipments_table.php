<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_shipments', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('order_id', 26);
            $table->string('status', 32);
            $table->string('tracking_number')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'order_id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_shipments');
    }
};
