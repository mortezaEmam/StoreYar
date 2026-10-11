<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price_amount')->default(0)->after('sku');
            $table->string('currency', 3)->default('IRR')->after('unit_price_amount');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table) {
            $table->dropColumn(['unit_price_amount', 'currency']);
        });
    }
};
