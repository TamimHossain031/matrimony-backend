<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The account (auth, credentials, role, status) stays separate from the
 * matrimony profile (blueprint §4). These columns are the account concern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');            // as entered
            $table->string('phone_e164')->nullable()->unique()->after('phone'); // normalized, one account per number
            // Salted hash of phone_e164, so the relative-blocklist exclusion (D2)
            // can be evaluated inside SQL in both directions. Raw number elsewhere.
            $table->string('phone_hash', 64)->nullable()->after('phone_e164');
            $table->string('role')->default('user')->after('password');
            $table->string('status')->default('active')->after('role');
            $table->date('date_of_birth')->nullable()->after('status');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('profile_created_by')->default('self')->after('gender');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->timestamp('terms_accepted_at')->nullable()->after('phone_verified_at');
            $table->timestamp('last_active_at')->nullable()->after('terms_accepted_at');
            $table->softDeletes();

            $table->index('role');
            $table->index('status');
            $table->index('phone_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone', 'phone_e164', 'phone_hash', 'role', 'status', 'date_of_birth',
                'gender', 'profile_created_by', 'phone_verified_at',
                'terms_accepted_at', 'last_active_at',
            ]);
        });
    }
};
