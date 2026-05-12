<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'type' column to import_logs to distinguish enrollment vs graduate imports
        Schema::table('import_logs', function (Blueprint $table) {
            $table->string('type', 30)->default('enrollment')->after('id');
        });

        // Per-record logging pivot for graduate imports
        Schema::create('import_log_graduate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_log_id')->constrained('import_logs')->cascadeOnDelete();
            $table->foreignId('graduate_id')->constrained('graduates')->cascadeOnDelete();
            $table->string('action', 20); // 'imported', 'updated', 'skipped'
            $table->json('changes')->nullable(); // What fields changed (for updates)
            $table->timestamps();

            $table->index(['import_log_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_log_graduate');

        Schema::table('import_logs', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
