<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Religion-specific attributes as a key/value set (blueprint §4 "Religion detail").
 * The allowed key set per religion is defined in code, not as 20 extra columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_religion_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('key');   // e.g. maslak, prayer_habit, hijab, caste, gotra
            $table->string('value');
            $table->timestamps();

            $table->unique(['profile_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_religion_attributes');
    }
};
