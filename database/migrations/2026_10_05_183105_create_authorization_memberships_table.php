<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_memberships', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('organization_id', 26);
            $table->string('user_id', 26);
            $table->string('role', 32);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index('user_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_memberships');
    }
};
