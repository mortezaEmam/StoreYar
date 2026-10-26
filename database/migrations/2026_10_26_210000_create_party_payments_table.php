<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_payments', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('party_type', 16); // CUSTOMER, SUPPLIER
            $table->string('party_id', 26);
            $table->string('direction', 8); // IN (from customer), OUT (to supplier)
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('IRR');
            $table->string('method', 32)->default('CASH'); // CASH, BANK, CARD
            $table->string('document_number', 64)->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 16)->default('POSTED');
            $table->string('journal_entry_id', 26)->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index(['organization_id', 'party_type', 'party_id'], 'pp_org_party_idx');
            $table->index(['organization_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_payments');
    }
};
