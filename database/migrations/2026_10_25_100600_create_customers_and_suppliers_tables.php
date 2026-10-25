<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('code', 64);
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('national_id', 32)->nullable();
            $table->string('customer_type', 32)->default('individual'); // individual, business
            $table->unsignedBigInteger('credit_limit_amount')->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->string('price_level', 32)->default('BASE');
            $table->boolean('is_active')->default(true);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('outstanding_balance_amount')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
            $table->index('phone');
        });

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('code', 64);
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('tax_id', 64)->nullable();
            $table->string('economic_code', 64)->nullable();
            $table->unsignedBigInteger('credit_limit_amount')->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('address')->nullable();
            $table->text('bank_info')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('outstanding_balance_amount')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
