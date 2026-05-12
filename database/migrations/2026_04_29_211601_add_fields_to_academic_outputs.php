<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_outputs', function (Blueprint $table) {
            $table->string('type_other_description')->nullable()->after('type');
            $table->string('drive_link')->nullable()->after('date_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_outputs', function (Blueprint $table) {
            $table->dropColumn(['type_other_description', 'drive_link']);
        });
    }
};
