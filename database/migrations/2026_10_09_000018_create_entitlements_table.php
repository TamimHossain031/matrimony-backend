<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitlements (§14). No payment gateway in the MVP — just the abstraction.
 * Everyone sits on `free`; the quotas double as the anti-spam system today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('plan')->default('free');
            $table->unsignedSmallInteger('daily_interest_limit')->default(10);
            $table->unsignedSmallInteger('daily_contact_view_limit')->default(20);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
    }
};
