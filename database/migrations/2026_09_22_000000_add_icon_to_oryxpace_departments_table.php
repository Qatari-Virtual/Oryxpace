<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('oryxpace_departments') && !Schema::hasColumn('oryxpace_departments', 'icon')) {
            Schema::table('oryxpace_departments', function (Blueprint $table) {
                $table->string('icon')->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('oryxpace_departments') && Schema::hasColumn('oryxpace_departments', 'icon')) {
            Schema::table('oryxpace_departments', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }
};
