<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_fiscal_years', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('name', 64);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 16)->default('OPEN'); // OPEN, CLOSED
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->unique(['organization_id', 'name'], 'afy_org_name_uq');
        });

        Schema::create('accounting_accounts', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('code', 32);
            $table->string('name');
            $table->string('type', 32); // ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE
            $table->string('subtype', 32)->nullable(); // CASH, BANK, AR, AP, INVENTORY, SALES, COGS, ...
            $table->string('parent_id', 26)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('currency', 3)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code'], 'aa_org_code_uq');
            $table->index(['organization_id', 'type']);
        });

        Schema::create('accounting_journal_entries', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('fiscal_year_id', 26)->nullable();
            $table->string('document_number', 64)->nullable();
            $table->string('status', 16)->default('DRAFT'); // DRAFT, POSTED, VOIDED
            $table->date('entry_date');
            $table->string('source_type', 64)->nullable(); // MANUAL, POS_SALE, GOODS_RECEIPT, ...
            $table->string('source_id', 26)->nullable();
            $table->string('memo')->nullable();
            $table->string('currency', 3)->default('IRR');
            $table->unsignedBigInteger('total_debit')->default(0);
            $table->unsignedBigInteger('total_credit')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->string('posted_by', 26)->nullable();
            $table->string('created_by', 26)->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('accounting_journal_lines', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('journal_entry_id', 26);
            $table->string('account_id', 26);
            $table->unsignedBigInteger('debit_amount')->default(0);
            $table->unsignedBigInteger('credit_amount')->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->string('memo')->nullable();
            $table->unsignedSmallInteger('line_no')->default(1);
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_lines');
        Schema::dropIfExists('accounting_journal_entries');
        Schema::dropIfExists('accounting_accounts');
        Schema::dropIfExists('accounting_fiscal_years');
    }
};
