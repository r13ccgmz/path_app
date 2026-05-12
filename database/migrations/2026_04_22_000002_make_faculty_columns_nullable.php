<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make several faculty columns nullable so CSV imports and partial records work.
     * The CSV only has: Name, Designation, Highest Educational Attainment.
     */
    public function up(): void
    {
        // Change non-nullable columns to nullable
        Schema::table('faculty', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->change();
            $table->date('birthday')->nullable()->change();
            $table->string('sex')->nullable()->change();
        });

        // For enum columns, raw SQL is needed
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `faculty` MODIFY `employment_status` ENUM('full-time','part-time','temporary','others') NULL DEFAULT NULL");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `faculty` MODIFY `highest_degree` ENUM('high-school','vocational','bachelors','masters','doctorate','n/a') NULL DEFAULT NULL");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `faculty` MODIFY `staff_classification` ENUM('admin','reps','faculty','others') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->string('email', 255)->nullable(false)->change();
            $table->date('birthday')->nullable(false)->change();
        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `faculty` MODIFY `sex` ENUM('male','female','other') NOT NULL");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `faculty` MODIFY `employment_status` ENUM('full-time','part-time','temporary','others') NOT NULL");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `faculty` MODIFY `highest_degree` ENUM('high-school','vocational','bachelors','masters','doctorate','n/a') NOT NULL");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `faculty` MODIFY `staff_classification` ENUM('admin','reps','faculty','others') NOT NULL");
    }
};
