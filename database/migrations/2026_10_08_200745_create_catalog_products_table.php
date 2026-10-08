<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_products', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('name');
            $table->string('sku');
            $table->string('status', 32);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'sku']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};
