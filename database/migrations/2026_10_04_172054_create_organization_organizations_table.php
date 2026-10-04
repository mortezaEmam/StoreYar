<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_organizations', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('name');
            $table->string('owner_user_id', 26);
            $table->string('status', 32);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('owner_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_organizations');
    }
};
