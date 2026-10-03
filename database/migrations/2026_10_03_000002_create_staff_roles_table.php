<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('level');
            $table->string('role_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_roles');
    }
};
