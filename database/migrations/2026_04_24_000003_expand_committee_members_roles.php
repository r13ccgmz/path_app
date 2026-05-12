<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Already applied by partial run:
        // - role enum expanded to include 'member'
        // - member_role varchar(100) column added
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\Schema::table('student_committee_members', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn('member_role');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `student_committee_members` MODIFY `role` ENUM('co-adviser','chair','co-chair','panel-member') NOT NULL");
    }
};
