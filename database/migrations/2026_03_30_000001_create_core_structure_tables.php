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
        Schema::create('oryxpace_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('oryxpace_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('oryxpace_departments')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('oryxpace_kanban_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('oryxpace_projects')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->integer('order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('required_permission')->nullable();
            $table->timestamps();
        });

        Schema::create('oryxpace_project_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('oryxpace_projects')->onDelete('cascade');
            $table->string('name');
            $table->string('color')->default('#3B82F6');
            $table->string('permission')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oryxpace_project_tags');
        Schema::dropIfExists('oryxpace_kanban_columns');
        Schema::dropIfExists('oryxpace_projects');
        Schema::dropIfExists('oryxpace_departments');
    }
};
