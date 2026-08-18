<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreignId('kanban_column_id')->nullable()->constrained('kanban_columns')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('resolution_user_id')->nullable();
            $table->unsignedBigInteger('created_by_pilot_id')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('order_in_column')->default(0);
            $table->boolean('is_template')->default(false);
            $table->timestamps();
        });

        Schema::create('task_assignees', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->unsignedBigInteger('pilot_id');
            $table->primary(['task_id', 'pilot_id']);
        });

        Schema::create('task_tags', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('project_tag_id')->constrained('project_tags')->onDelete('cascade');
            $table->primary(['task_id', 'project_tag_id']);
        });

        Schema::create('task_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->unsignedBigInteger('pilot_id')->nullable();
            $table->string('activity_type');
            $table->text('description');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->string('name');
            $table->string('field_type')->default('text');
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('task_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('custom_field_id')->constrained('custom_fields')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'custom_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_custom_field_values');
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('task_activity_logs');
        Schema::dropIfExists('task_tags');
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
    }
};
