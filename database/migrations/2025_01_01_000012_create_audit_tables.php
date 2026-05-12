<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('data_transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('transfer_type', ['import', 'export']);
            $table->enum('data_type', [
                'students', 'faculty', 'courses', 'enrollments',
                'grades', 'milestones', 'programs', 'academic_outputs',
            ]);
            $table->string('file_name', 255)->nullable();
            $table->enum('file_format', ['csv', 'xlsx', 'pdf'])->nullable();
            $table->integer('total_rows')->nullable();
            $table->integer('successful_rows')->nullable();
            $table->integer('failed_rows')->nullable();
            $table->json('error_details')->nullable();
            $table->json('filters_applied')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->foreign('initiated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('model_type', 255)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('data_transfer_logs');
    }
};
