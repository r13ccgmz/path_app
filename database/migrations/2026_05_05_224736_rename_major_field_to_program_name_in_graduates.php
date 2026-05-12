<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->renameColumn('major_field', 'program_name');
        });

        Schema::table('graduates', function (Blueprint $table) {
            $table->string('major_field_raw')->nullable()->after('program_name');
        });
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropColumn('major_field_raw');
        });

        Schema::table('graduates', function (Blueprint $table) {
            $table->renameColumn('program_name', 'major_field');
        });
    }
};
