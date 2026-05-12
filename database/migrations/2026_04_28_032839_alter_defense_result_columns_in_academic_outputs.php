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
        Schema::table('academic_outputs', function (Blueprint $table) {
            $table->string('proposal_defense_result', 255)->nullable()->change();
            $table->string('final_defense_result', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_outputs', function (Blueprint $table) {
            $table->enum('proposal_defense_result', ['passed', 'failed', 'conditional'])->nullable()->change();
            $table->enum('final_defense_result', ['passed', 'failed', 'conditional'])->nullable()->change();
        });
    }
};
