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
        Schema::table('academic_output_committee', function (Blueprint $table) {
            $table->string('role', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_output_committee', function (Blueprint $table) {
            // Revert back to enum
            $table->enum('role', ['adviser', 'co-adviser', 'chair', 'co-chair', 'member'])->change();
        });
    }
};
