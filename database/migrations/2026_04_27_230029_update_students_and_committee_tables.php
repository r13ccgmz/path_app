<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify role column to string to support new dynamic roles
        DB::statement("ALTER TABLE student_committee_members MODIFY COLUMN role VARCHAR(255) NOT NULL");

        // Migrate existing roles to new Title Case format
        DB::table('student_committee_members')->where('role', 'co-adviser')->update(['role' => 'Co-Adviser']);
        DB::table('student_committee_members')->where('role', 'chair')->update(['role' => 'Chair']);
        DB::table('student_committee_members')->where('role', 'co-chair')->update(['role' => 'Co-Chair']);
        DB::table('student_committee_members')->where('role', 'panel-member')->update(['role' => 'Member']);
        DB::table('student_committee_members')->where('role', 'member')->update(['role' => 'Member']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert roles to previous enum values (lossy if new roles were added)
        DB::table('student_committee_members')->where('role', 'Co-Adviser')->update(['role' => 'co-adviser']);
        DB::table('student_committee_members')->where('role', 'Chair')->update(['role' => 'chair']);
        DB::table('student_committee_members')->where('role', 'Co-Chair')->update(['role' => 'co-chair']);
        DB::table('student_committee_members')->where('role', 'Member')->update(['role' => 'panel-member']);
        
        // Revert column back to enum (might fail if unsupported strings exist, but this is best effort)
        DB::statement("ALTER TABLE student_committee_members MODIFY COLUMN role ENUM('co-adviser', 'chair', 'co-chair', 'member', 'panel-member') NOT NULL");
    }
};
