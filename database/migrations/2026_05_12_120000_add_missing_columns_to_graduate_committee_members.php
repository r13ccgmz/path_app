<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduate_committee_members', function (Blueprint $table) {
            $table->date('appointed_date')->nullable()->after('match_type');
            $table->foreignId('term_start_id')->nullable()->constrained('semesters')->nullOnDelete()->after('appointed_date');
            $table->foreignId('term_end_id')->nullable()->constrained('semesters')->nullOnDelete()->after('term_start_id');
        });
    }

    public function down(): void
    {
        Schema::table('graduate_committee_members', function (Blueprint $table) {
            $table->dropForeign(['term_start_id']);
            $table->dropForeign(['term_end_id']);
            $table->dropColumn(['appointed_date', 'term_start_id', 'term_end_id']);
        });
    }
};
