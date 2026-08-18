<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pilots')) {
            Schema::create('pilots', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('callsign')->unique();
                $table->text('email');
                $table->text('password');
                $table->text('ifc')->nullable();
                $table->integer('status')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pilots');
    }
};
