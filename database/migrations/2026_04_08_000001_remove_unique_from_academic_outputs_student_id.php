<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_outputs', function (Blueprint $table) {
            // Must drop FK first, then the unique index, then re-add FK without unique
            $table->dropForeign(['student_id']);
            $table->dropUnique(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('academic_outputs', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropIndex(['student_id']);
            $table->unique('student_id');
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }
};
