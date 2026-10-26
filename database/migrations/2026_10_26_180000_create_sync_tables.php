<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_devices', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('device_key', 64); // client-generated stable id
            $table->string('name')->nullable();
            $table->string('platform', 32)->nullable(); // android, ios, web, electron
            $table->string('app_version', 32)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status', 16)->default('ACTIVE');
            $table->timestamps();

            $table->unique(['organization_id', 'device_key'], 'sync_dev_org_key_uq');
            $table->index('organization_id');
        });

        Schema::create('sync_events', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('device_id', 26)->nullable();
            $table->string('client_event_id', 64); // idempotency from client
            $table->string('event_type', 64); // POS_SALE, STOCK_ADJUST, ...
            $table->json('payload');
            $table->string('status', 16)->default('PENDING'); // PENDING, APPLIED, FAILED, DUPLICATE
            $table->string('result_ref', 26)->nullable(); // e.g. pos_sale id
            $table->text('error_message')->nullable();
            $table->timestamp('client_occurred_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'client_event_id'], 'sync_evt_org_client_uq');
            $table->index(['organization_id', 'status']);
            $table->index('device_id');
        });

        Schema::create('sync_cursors', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('device_id', 26);
            $table->string('stream', 64); // catalog, stock, prices
            $table->string('cursor_value', 64)->default('0');
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['organization_id', 'device_id', 'stream'], 'sync_cur_org_dev_stream_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_cursors');
        Schema::dropIfExists('sync_events');
        Schema::dropIfExists('sync_devices');
    }
};
