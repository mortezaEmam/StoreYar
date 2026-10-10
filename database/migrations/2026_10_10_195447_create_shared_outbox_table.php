<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_outbox', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('aggregate_type', 128);
            $table->string('aggregate_id', 26);
            $table->string('event_type', 255);
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->index(['processed_at', 'created_at']);
            $table->index(['aggregate_type', 'aggregate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_outbox');
    }
};
