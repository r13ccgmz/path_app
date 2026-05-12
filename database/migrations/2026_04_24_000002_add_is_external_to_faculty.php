<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->boolean('is_external')->default(false)->after('account_status');
        });

        // Flag existing external members
        DB::table('faculty')
            ->where('designation', 'External Member')
            ->update(['is_external' => true]);
    }

    public function down(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->dropColumn('is_external');
        });
    }
};
