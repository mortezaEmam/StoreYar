<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_inbox_messages', function (Blueprint $table): void {
            $table->string('id', 26)->primary();

            $table->string('event_id', 26)->unique();

            $table->unsignedInteger('version');

            $table->string('event_type', 255);

            $table->string('business_id', 26);

            $table->string('operation_id', 26);
            $table->string('correlation_id', 26);
            $table->string('causation_id', 26)->nullable();

            $table->dateTime('occurred_at', precision: 6);

            $table->json('payload');

            $table->dateTime('processed_at', precision: 6)->nullable();

            $table->timestamps();

            $table->index(
                ['business_id', 'processed_at'],
                'integration_inbox_business_processed_idx',
            );

            $table->index(
                ['event_type', 'processed_at'],
                'integration_inbox_event_type_processed_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_inbox_messages');
    }
};
