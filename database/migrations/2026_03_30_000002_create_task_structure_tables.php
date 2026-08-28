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
        Schema::create('oryxpace_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('oryxpace_projects')->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('oryxpace_tasks')->onDelete('cascade');
            $table->foreignId('kanban_column_id')->nullable()->constrained('oryxpace_kanban_columns')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('resolution_user_id')->nullable();
            $table->unsignedBigInteger('created_by_pilot_id')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('order_in_column')->default(0);
            $table->boolean('is_template')->default(false);
            $table->timestamps();
        });

        Schema::create('oryxpace_task_assignees', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('oryxpace_tasks')->onDelete('cascade');
            $table->unsignedBigInteger('pilot_id');
            $table->primary(['task_id', 'pilot_id']);
        });

        Schema::create('oryxpace_task_tags', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('oryxpace_tasks')->onDelete('cascade');
            $table->foreignId('project_tag_id')->constrained('oryxpace_project_tags')->onDelete('cascade');
            $table->primary(['task_id', 'project_tag_id']);
        });

        Schema::create('oryxpace_task_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('oryxpace_tasks')->onDelete('cascade');
            $table->unsignedBigInteger('pilot_id')->nullable();
            $table->string('activity_type');
            $table->text('description');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();
        });

        Schema::create('oryxpace_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('oryxpace_projects')->onDelete('cascade');
            $table->string('name');
            $table->string('field_type')->default('text');
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('oryxpace_task_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('oryxpace_tasks')->onDelete('cascade');
            $table->foreignId('custom_field_id')->constrained('oryxpace_custom_fields')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'custom_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oryxpace_task_custom_field_values');
        Schema::dropIfExists('oryxpace_custom_fields');
        Schema::dropIfExists('oryxpace_task_activity_logs');
        Schema::dropIfExists('oryxpace_task_tags');
        Schema::dropIfExists('oryxpace_task_assignees');
        Schema::dropIfExists('oryxpace_tasks');
    }
};
