<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['household_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_members');
    }
};
