<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guardian & managed profiles (blueprint §5). Most profiles in this market are
 * created and managed by a parent/sibling/relative. Permissions are checked by
 * policy, never by role name alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the managing account
            $table->string('relationship'); // self | parent | sibling | relative
            // Granular permissions, checked by policy.
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_shortlist')->default(false);
            $table->boolean('can_send_interest')->default(false);
            $table->boolean('can_chat')->default(false);
            $table->timestamps();

            $table->unique(['profile_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_managers');
    }
};
