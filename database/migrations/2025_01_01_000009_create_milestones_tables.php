<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('milestone_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->boolean('applies_to_all_programs')->default(false);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('category', [
                'coursework', 'examination', 'research',
                'publication', 'defense', 'other',
            ]);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->enum('degree_level', ['masters', 'doctorate'])->nullable();
            $table->timestamps();
        });

        Schema::create('student_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('milestone_template_id')->nullable()->constrained('milestone_templates')->nullOnDelete();
            $table->string('name', 255);
            $table->enum('category', [
                'coursework', 'examination', 'research',
                'publication', 'defense', 'other',
            ]);
            $table->enum('status', ['pending', 'in-progress', 'completed', 'waived'])->default('pending');
            $table->date('date_started')->nullable();
            $table->date('date_completed')->nullable();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->string('supporting_document', 255)->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_milestones');
        Schema::dropIfExists('milestone_templates');
    }
};
