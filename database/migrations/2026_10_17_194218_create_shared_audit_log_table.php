<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_audit_log', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26)->nullable()->index();
            $table->string('actor_user_id', 26)->nullable()->index();
            $table->string('action', 128);
            $table->string('resource_type', 64);
            $table->string('resource_id', 26)->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'occurred_at']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_audit_log');
    }
};
