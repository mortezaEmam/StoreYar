<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_units', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('code', 32);
            $table->string('name');
            $table->string('symbol', 16)->nullable();
            $table->boolean('is_base')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
        });

        Schema::create('catalog_unit_conversions', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('from_unit_id', 26);
            $table->string('to_unit_id', 26);
            $table->decimal('factor', 18, 8);
            $table->timestamps();

            $table->unique(['from_unit_id', 'to_unit_id']);
            $table->index('organization_id');
        });

        Schema::create('catalog_brands', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('code', 64)->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_brands');
        Schema::dropIfExists('catalog_unit_conversions');
        Schema::dropIfExists('catalog_units');
    }
};
