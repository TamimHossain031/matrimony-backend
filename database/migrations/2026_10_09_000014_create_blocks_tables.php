<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blocks (§16) and the relative blocklist (§11, D2).
 *
 * profile_contact_blocks store only a salted hash of the phone number — raw
 * numbers are never stored. Any account whose normalized phone hashes into a
 * profile's blocklist is excluded from discovery in both directions, silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
            $table->index('blocked_id');
        });

        Schema::create('profile_contact_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('phone_hash', 64); // HMAC-SHA256, never the raw number
            $table->string('label')->nullable(); // optional owner-only memo, e.g. "cousin"
            $table->timestamp('created_at')->nullable();

            $table->unique(['profile_id', 'phone_hash']);
            $table->index('phone_hash'); // both-directions lookup
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_contact_blocks');
        Schema::dropIfExists('blocks');
    }
};
