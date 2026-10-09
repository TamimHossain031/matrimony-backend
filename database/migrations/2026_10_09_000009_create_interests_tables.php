<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interest system (§8). One current-state row per (sender, receiver) direction,
 * plus an append-only interest_events history table — the v1 normalization fix
 * that lets "no duplicate active interest" and "preserve history" both hold.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|accepted|rejected|withdrawn|expired|disconnected
            $table->text('message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // One current row per direction; history lives in interest_events.
            $table->unique(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'status']);
            $table->index(['sender_id', 'status']);
            $table->index('expires_at');
        });

        Schema::create('interest_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');         // sent | accepted | rejected | withdrawn | expired | disconnected
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->timestamp('created_at')->nullable();

            $table->index('interest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_events');
        Schema::dropIfExists('interests');
    }
};
