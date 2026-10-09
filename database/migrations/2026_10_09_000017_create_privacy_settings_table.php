<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Privacy settings (§11). Per-field visibility levels, enforced by the backend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone_visibility')->default('private');
            $table->string('email_visibility')->default('private');
            $table->string('address_visibility')->default('accepted_connection');
            $table->string('family_contact_visibility')->default('accepted_connection');
            $table->string('photo_visibility')->default('members_only');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_settings');
    }
};
