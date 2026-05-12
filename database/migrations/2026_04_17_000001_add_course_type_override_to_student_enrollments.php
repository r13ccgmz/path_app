<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('student_enrollments', 'course_type_override')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->string('course_type_override')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn('course_type_override');
        });
    }
};
