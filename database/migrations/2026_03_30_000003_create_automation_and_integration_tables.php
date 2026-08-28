<?php

/*
 * DO NOT RUN THIS MIGRATION.
 *
 * Hand-made migrations are already in place in the shared database. Review
 * and apply database/sql/oryxpace_schema.sql manually instead. Continue with
 * a new versioned migration for future schema changes after manual approval.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oryxpace_task_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('project_id')->constrained('oryxpace_projects')->onDelete('cascade');
            $table->string('cron_expression')->default('0 0 * * *');
            $table->json('subtask_templates')->nullable();
            $table->json('custom_fields_template')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('oryxpace_project_subscriptions', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained('oryxpace_projects')->onDelete('cascade');
            $table->unsignedBigInteger('pilot_id');
            $table->timestamps();

            $table->primary(['project_id', 'pilot_id']);
        });

        Schema::create('oryxpace_user_notifications_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pilot_id');
            $table->string('channel')->default('discord');
            $table->string('event_type')->default('all');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['pilot_id', 'channel', 'event_type']);
        });

        Schema::create('oryxpace_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oryxpace_custom_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('oryxpace_pilot_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('pilot_id');
            $table->foreignId('permission_id')->constrained('oryxpace_custom_permissions')->onDelete('cascade');
            $table->primary(['pilot_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oryxpace_pilot_permissions');
        Schema::dropIfExists('oryxpace_custom_permissions');
        Schema::dropIfExists('oryxpace_api_keys');
        Schema::dropIfExists('oryxpace_user_notifications_settings');
        Schema::dropIfExists('oryxpace_project_subscriptions');
        Schema::dropIfExists('oryxpace_task_schedules');
    }
};
