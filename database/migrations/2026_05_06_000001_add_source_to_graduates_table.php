<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->string('source')->nullable()->after('match_type');
        });

        // Backfill: records created via StudentHistory (match_type='manual' AND student_number set at creation)
        // are 'manual' source. Everything else is 'imported' from GS Excel upload.
        // Since we can't perfectly distinguish retroactively, mark all existing records as 'imported'
        // and only those with match_type='manual' that were created by the user as 'manual'.
        // The match_type='manual' could mean either "manually created" OR "manually linked",
        // so we check: if match_type='manual' AND there is NO corresponding GraduateImport record,
        // it was likely created manually. As a safe default, mark all as 'imported'.
        DB::table('graduates')->whereNull('source')->update(['source' => 'imported']);
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
