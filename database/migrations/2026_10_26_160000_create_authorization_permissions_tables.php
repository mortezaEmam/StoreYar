<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_permissions', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('code', 64)->unique(); // e.g. pos.sale, inventory.receive
            $table->string('name');
            $table->string('module', 32); // pos, inventory, catalog, purchasing, accounting, reports, admin
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('module');
        });

        Schema::create('authorization_role_permissions', function (Blueprint $table): void {
            $table->string('id', 26)->primary();
            $table->string('role', 32); // owner, admin, member, cashier, warehouse, accountant
            $table->string('permission_code', 64);
            $table->timestamps();

            $table->unique(['role', 'permission_code'], 'arp_role_perm_uq');
            $table->index('permission_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_role_permissions');
        Schema::dropIfExists('authorization_permissions');
    }
};
