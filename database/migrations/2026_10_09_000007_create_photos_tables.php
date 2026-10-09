<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photo system (blueprint §12). Photos blurred by default for non-connections;
 * a request flow unlocks them. Private photos must not be reachable by guessing
 * a path — storage uses opaque keys and signed URLs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');                 // opaque storage key, not a guessable path
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('visibility')->default('members_only');
            $table->string('moderation_status')->default('pending'); // pending | approved | rejected
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['profile_id', 'position']);
            $table->index('moderation_status');
        });

        Schema::create('photo_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();   // owner of the photos
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending | approved | denied
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'requester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_requests');
        Schema::dropIfExists('profile_photos');
    }
};
