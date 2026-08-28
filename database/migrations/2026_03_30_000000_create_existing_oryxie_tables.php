<?php

/*
 * DO NOT RUN THIS MIGRATION AGAINST THE SHARED DATABASE.
 *
 * The shared Crew Center schema is already managed externally. The approved
 * hand-made SQL is in database/sql/oryxpace_schema.sql. Keep this migration
 * as historical/reference code and continue with a new versioned migration
 * only after its SQL has been reviewed and applied manually.
 */

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
