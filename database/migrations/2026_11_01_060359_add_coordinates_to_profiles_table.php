<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Precise coordinates for radius ("near me") discovery. Nullable:
            // only set once the member shares their location from the app.
            $table->decimal('latitude', 10, 7)->nullable()->after('upazila_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            // Supports the bounding-box pre-filter in DiscoveryService.
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
