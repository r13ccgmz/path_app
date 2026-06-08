<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copy override values into total_units_required before dropping the column
        DB::statement('UPDATE programs SET total_units_required = total_units_override WHERE total_units_override IS NOT NULL');

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('total_units_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_units_override')->nullable()->after('total_units_required');
        });
    }
};
