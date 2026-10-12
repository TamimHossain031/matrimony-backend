<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            // Server-generated blurred derivative, safe to show to viewers who may not see the original.
            $table->string('blurred_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->dropColumn('blurred_path');
        });
    }
};
