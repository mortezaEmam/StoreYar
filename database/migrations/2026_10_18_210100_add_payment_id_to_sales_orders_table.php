<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->string('payment_id', 26)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->dropColumn('payment_id');
        });
    }
};
