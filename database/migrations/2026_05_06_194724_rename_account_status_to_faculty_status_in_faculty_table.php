<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename faculty.account_status → faculty.faculty_status
     * to distinguish it from users.account_status (login access).
     *
     * Uses raw SQL because Laravel's renameColumn has issues with
     * MySQL ENUM columns and default values.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `faculty` CHANGE `account_status` `faculty_status` ENUM('active','inactive') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `faculty` CHANGE `faculty_status` `account_status` ENUM('active','inactive') NOT NULL DEFAULT 'active'");
    }
};
