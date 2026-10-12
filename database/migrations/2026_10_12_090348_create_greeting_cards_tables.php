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
        Schema::create('greeting_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('occasion', 50)->index();
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->json('design_config')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('greeting_cards', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 32)->unique();
            $table->string('edit_token', 64)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('template_id')->constrained('greeting_templates')->cascadeOnDelete();
            $table->string('occasion', 50)->index();
            $table->string('recipient_name', 100);
            $table->string('sender_name', 100)->nullable();
            $table->string('title', 150)->nullable();
            $table->text('message');
            $table->json('theme_settings')->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->string('privacy', 20)->default('public_link');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('greeting_card_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('greeting_cards')->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->string('session_id', 64)->nullable();
            $table->string('referrer_category', 50)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('greeting_card_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('greeting_cards')->cascadeOnDelete();
            $table->string('reason', 100);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('reporter_ip_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('greeting_card_reports');
        Schema::dropIfExists('greeting_card_events');
        Schema::dropIfExists('greeting_cards');
        Schema::dropIfExists('greeting_templates');
    }
};
