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
        DB::statement("ALTER TABLE student_milestones MODIFY COLUMN status ENUM('pending', 'in-progress', 'completed', 'waived') NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE student_milestones MODIFY COLUMN status ENUM('pending', 'in-progress', 'completed', 'waived') NOT NULL DEFAULT 'pending'");
    }
};
