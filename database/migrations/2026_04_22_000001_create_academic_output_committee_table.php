<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_output_committee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_output_id')->constrained('academic_outputs')->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculty')->nullOnDelete();
            $table->string('name', 255)->nullable(); // Free-text for non-faculty members
            $table->enum('role', ['adviser', 'co-adviser', 'chair', 'co-chair', 'member']);
            $table->timestamps();

            $table->index('academic_output_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_output_committee');
    }
};
