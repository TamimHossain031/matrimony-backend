<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner preference (§6). One row of ranges per profile, plus a weights table
 * that marks each preference key as deal_breaker | nice_to_have — the input to
 * the transparent matching feature (D3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->unsignedSmallInteger('height_min_cm')->nullable();
            $table->unsignedSmallInteger('height_max_cm')->nullable();
            $table->json('marital_statuses')->nullable();  // array of allowed statuses
            $table->foreignId('religion_id')->nullable()->constrained('religions')->nullOnDelete();
            $table->foreignId('min_education_level_id')->nullable()->constrained('education_levels')->nullOnDelete();
            $table->json('profession_ids')->nullable();    // array of acceptable profession ids
            $table->unsignedInteger('income_min')->nullable();
            $table->json('district_ids')->nullable();      // array of acceptable district ids
            $table->text('other_expectations')->nullable();
            $table->timestamps();
        });

        Schema::create('preference_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_preference_id')->constrained()->cascadeOnDelete();
            $table->string('preference_key'); // age_range | height_range | religion | education | profession | income | district | marital_status
            $table->string('importance');     // deal_breaker | nice_to_have
            $table->timestamps();

            $table->unique(['partner_preference_id', 'preference_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preference_weights');
        Schema::dropIfExists('partner_preferences');
    }
};
