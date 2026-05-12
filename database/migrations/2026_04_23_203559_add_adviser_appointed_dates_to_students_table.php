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
        Schema::table('students', function (Blueprint $table) {
            $table->date('adviser_appointed_date')->nullable()->after('adviser_id');
            $table->date('temporary_adviser_appointed_date')->nullable()->after('temporary_adviser_id');
            $table->date('registration_adviser_appointed_date')->nullable()->after('registration_adviser_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'adviser_appointed_date',
                'temporary_adviser_appointed_date',
                'registration_adviser_appointed_date',
            ]);
        });
    }
};
