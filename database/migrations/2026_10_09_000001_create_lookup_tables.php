<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lookup tables — mandatory, not optional (blueprint §13).
 * Free-text profession/location makes filtering impossible forever.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bangladeshi taxonomy: division → district → upazila, in one adjacency table.
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('type'); // division | district | upazila
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->timestamps();

            $table->index(['type', 'parent_id']);
            $table->index('name_en');
        });

        Schema::create('religions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // islam, hindu, christian, buddhist, other
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->timestamps();
        });

        Schema::create('professions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->timestamps();
        });

        Schema::create('education_levels', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->unsignedSmallInteger('rank')->default(0); // for ordering / range filters
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_levels');
        Schema::dropIfExists('professions');
        Schema::dropIfExists('religions');
        Schema::dropIfExists('locations');
    }
};
