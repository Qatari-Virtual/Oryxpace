<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('oryxpace_permission_access')) {
            Schema::create('oryxpace_permission_access', function (Blueprint $table) {
                $table->id();
                $table->foreignId('permission_id')->constrained('oryxpace_custom_permissions')->onDelete('cascade');
                $table->foreignId('department_id')->nullable()->constrained('oryxpace_departments')->onDelete('cascade');
                $table->foreignId('project_id')->nullable()->constrained('oryxpace_projects')->onDelete('cascade');
                $table->timestamps();

                $table->index(['permission_id', 'department_id']);
                $table->index(['permission_id', 'project_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oryxpace_permission_access');
    }
};
