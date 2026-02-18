<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Laravel default
            $table->string('name')->nullable(); // nálatok nullable kellett a full_name miatt

            // MagicFridge
            $table->string('full_name', 40);

            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('password');
            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
