<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('branch_id', 26)->nullable();
            $table->string('document_type', 64);
            $table->unsignedSmallInteger('fiscal_year')->nullable();
            $table->string('series', 16)->default('DEFAULT');
            $table->string('prefix', 32)->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();

            $table->unique(
                ['organization_id', 'branch_id', 'document_type', 'fiscal_year', 'series'],
                'document_sequences_unique'
            );
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
