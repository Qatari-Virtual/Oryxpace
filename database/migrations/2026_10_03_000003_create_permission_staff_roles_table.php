<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oryxpace_permission_staff_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('oryxpace_custom_permissions')->onDelete('cascade');
            $table->unsignedBigInteger('staff_role_id');
            $table->timestamps();

            $table->unique(['permission_id', 'staff_role_id'], 'oryx_perm_staff_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oryxpace_permission_staff_roles');
    }
};
