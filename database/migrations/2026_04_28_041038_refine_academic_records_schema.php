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
            $table->dropColumn('expected_graduation_date');
        });

        Schema::table('student_committee_members', function (Blueprint $table) {
            $table->dropColumn('member_role');
        });

        Schema::table('graduates', function (Blueprint $table) {
            $table->json('committee_data')->nullable();
            $table->string('major')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropColumn(['committee_data', 'major']);
        });

        Schema::table('student_committee_members', function (Blueprint $table) {
            $table->string('member_role', 100)->nullable();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->date('expected_graduation_date')->nullable();
        });
    }
};
