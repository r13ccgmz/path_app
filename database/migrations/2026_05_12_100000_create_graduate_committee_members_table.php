<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduate_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graduate_id')->constrained('graduates')->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculty')->nullOnDelete();
            $table->string('name', 255)->nullable(); // Original text name from CSV / fallback
            $table->string('role', 50); // 'Chair', 'Co-Chair', 'Member', 'Adviser'
            $table->string('match_type', 20)->nullable(); // 'auto', 'manual'
            $table->timestamps();

            $table->index('graduate_id');
            $table->index('faculty_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduate_committee_members');
    }
};
