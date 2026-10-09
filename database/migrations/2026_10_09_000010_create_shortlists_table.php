<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shortlist / favorites (§9). Always private to the owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();     // owner
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();  // shortlisted profile
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlists');
    }
};
