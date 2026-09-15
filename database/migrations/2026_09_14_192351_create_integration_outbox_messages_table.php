<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_outbox_messages', function (Blueprint $table): void {
            $table->string('id', 26)->primary();

            $table->string('event_id', 26)->unique();

            $table->unsignedInteger('version');

            $table->string('event_type', 255);

            $table->string('business_id', 26);
            $table->string('aggregate_id', 26);
            $table->string('aggregate_type', 255);

            $table->string('operation_id', 26);
            $table->string('correlation_id', 26);
            $table->string('causation_id', 26)->nullable();

            $table->dateTime('occurred_at', precision: 6);

            $table->json('payload');

            $table->dateTime('published_at', precision: 6)->nullable();

            $table->unsignedInteger('attempts')->default(0);

            $table->dateTime('available_at', precision: 6)->nullable();

            $table->dateTime('last_attempted_at', precision: 6)->nullable();

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(
                ['business_id', 'published_at'],
                'integration_outbox_business_published_idx',
            );

            $table->index(
                ['published_at', 'available_at'],
                'integration_outbox_publish_queue_idx',
            );

            $table->index(
                ['aggregate_type', 'aggregate_id'],
                'integration_outbox_aggregate_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outbox_messages');
    }
};
