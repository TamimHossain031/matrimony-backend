<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The profile. Keep every searchable field here, indexed (blueprint §13).
 * career / family / lifestyle live as columns here rather than joined tables —
 * the normalization fix from v1. Only genuinely multi-row data (photos,
 * education history, religion attributes) gets its own table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            // The candidate the profile is about. One profile per candidate user.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('public_id')->unique(); // non-sequential public Profile ID

            // Basic
            $table->string('full_name_en');
            $table->string('full_name_bn')->nullable();
            $table->string('gender');                        // denormalized from users for search
            $table->date('date_of_birth');                   // age is ALWAYS computed, never stored
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->string('marital_status')->nullable();    // never_married | divorced | widowed | separated
            $table->foreignId('district_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('nationality')->default('Bangladeshi');
            $table->string('headline')->nullable();
            $table->text('about')->nullable();

            // Religion
            $table->foreignId('religion_id')->nullable()->constrained('religions')->nullOnDelete();

            // Education (highest — searchable). History lives in `educations`.
            $table->foreignId('education_level_id')->nullable()->constrained('education_levels')->nullOnDelete();
            $table->string('institution')->nullable();
            $table->string('subject')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();

            // Career
            $table->foreignId('profession_id')->nullable()->constrained('professions')->nullOnDelete();
            $table->string('job_title')->nullable();
            $table->string('organization')->nullable();
            $table->unsignedInteger('income_min')->nullable(); // BDT/month range
            $table->unsignedInteger('income_max')->nullable();
            $table->string('work_location')->nullable();

            // Family (kept as columns — no join)
            $table->string('father_occupation')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->unsignedTinyInteger('siblings_count')->nullable();
            $table->string('family_type')->nullable(); // nuclear | joint
            $table->string('family_location')->nullable();
            $table->text('family_description')->nullable();

            // Lifestyle (low-cardinality, non-searched detail → validated JSON)
            $table->json('lifestyle')->nullable(); // smoking, drinking, diet, hobbies[], languages[]

            // Derived / housekeeping
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->unsignedTinyInteger('verification_level')->default(0);
            $table->boolean('has_photo')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for the filtered discovery query (the query to watch, §25).
            $table->index(['gender', 'date_of_birth']);
            $table->index('district_id');
            $table->index('religion_id');
            $table->index('profession_id');
            $table->index('education_level_id');
            $table->index('marital_status');
            $table->index('height_cm');
            $table->index('verification_level');
            $table->index('has_photo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
