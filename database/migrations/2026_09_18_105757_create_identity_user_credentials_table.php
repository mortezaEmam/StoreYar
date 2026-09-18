<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('identity_user_credentials', function (Blueprint $table): void {
            $table->string('user_id', 26)->primary();
            $table->string('password_hash');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('identity_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_user_credentials');
    }
};
