<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cleanup: remove redundant/legacy columns and tables.
     * - users.role enum column → replaced by Spatie model_has_roles pivot
     * - data_transfers table → legacy, no model or code references
     * - audit_logs table → superseded by spatie activity_log table
     */
    public function up(): void
    {
        // 1. Drop the redundant 'role' enum column from users
        //    Roles are now managed entirely via Spatie/Shield (model_has_roles pivot)
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // 2. Drop legacy audit tables (superseded by spatie/laravel-activitylog)
        Schema::dropIfExists('data_transfers');
        Schema::dropIfExists('audit_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the role enum column on users
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super-admin', 'admin', 'secretary', 'user', 'observer'])
                ->default('user')
                ->after('password');
        });

        // Restore data_transfers table
        Schema::create('data_transfers', function (Blueprint $table) {
            $table->id();
            $table->enum('transfer_type', ['import', 'export']);
            $table->enum('data_type', ['enrollees', 'courses', 'grades', 'faculty', 'students', 'milestones']);
            $table->string('file_name')->nullable();
            $table->enum('file_format', ['csv', 'xlsx', 'pdf'])->nullable();
            $table->integer('total_rows')->nullable();
            $table->integer('successful_rows')->nullable();
            $table->integer('failed_rows')->nullable();
            $table->json('error_details')->nullable();
            $table->json('filters_applied')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->foreignId('initiated_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Restore audit_logs table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action', 50);
            $table->string('model_type')->nullable();
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
};
