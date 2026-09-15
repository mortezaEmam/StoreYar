<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_idempotency_keys', function (Blueprint $table): void {
            $table->string('id', 26)->primary();

            $table->string('business_id', 26);

            $table->string('operation_id', 26);

            $table->string('operation_type', 255);

            $table->string('status', 50);

            $table->json('response')->nullable();

            $table->dateTime('completed_at', precision: 6)->nullable();

            $table->timestamps();

            $table->unique(
                ['business_id', 'operation_id'],
                'integration_idempotency_business_operation_unique',
            );

            $table->index(
                ['business_id', 'status'],
                'integration_idempotency_business_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_idempotency_keys');
    }
};
