<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_categories', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('name');
            $table->timestamps();

            $table->index('organization_id');
            $table->unique(['organization_id', 'name']);
        });

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->string('category_id', 26)->nullable()->after('organization_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropIndex(['category_id']);
            $table->dropColumn('category_id');
        });
        Schema::dropIfExists('catalog_categories');
    }
};
