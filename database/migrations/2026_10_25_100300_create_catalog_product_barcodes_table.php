<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_product_barcodes', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('product_id', 26);
            $table->string('variant_id', 26)->nullable();
            $table->string('barcode', 64);
            $table->string('type', 32)->default('INTERNAL'); // EAN13, UPC, CODE128, INTERNAL, SUPPLIER
            $table->string('unit_id', 26)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'barcode']);
            $table->index(['organization_id', 'product_id']);
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_barcodes');
    }
};
