<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('academic_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->string('title', 500)->nullable();
            $table->enum('type', ['thesis', 'dissertation', 'field-study']);
            $table->text('abstract')->nullable();
            $table->enum('status', [
                'topic-approved', 'proposal-writing', 'proposal-defended',
                'data-collection', 'writing', 'final-defense-scheduled',
                'defended', 'revising', 'submitted', 'approved',
            ])->default('topic-approved');

            $table->date('proposal_defense_date')->nullable();
            $table->enum('proposal_defense_result', ['passed', 'failed', 'conditional'])->nullable();
            $table->text('proposal_defense_remarks')->nullable();

            $table->date('final_defense_date')->nullable();
            $table->enum('final_defense_result', ['passed', 'failed', 'conditional'])->nullable();
            $table->text('final_defense_remarks')->nullable();

            $table->date('date_submitted')->nullable();
            $table->text('keywords')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_outputs');
    }
};
