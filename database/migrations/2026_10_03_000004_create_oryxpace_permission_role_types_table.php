<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oryxpace_permission_role_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('oryxpace_custom_permissions')->onDelete('cascade');
            $table->string('role_name');
            $table->string('level');
            $table->timestamps();

            $table->unique(['permission_id', 'role_name', 'level'], 'oryx_perm_role_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oryxpace_permission_role_types');
    }
};
