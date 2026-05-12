<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // ── Contact Details (expanded) ──
            $table->string('up_email', 255)->nullable()->after('email')
                ->comment('Official UP institutional email');
            $table->string('alternative_email', 255)->nullable()->after('up_email')
                ->comment('Alternative/personal email');
            $table->string('primary_contact_number', 20)->nullable()->after('contact_number')
                ->comment('Primary cellphone number');
            $table->string('alternative_contact_number', 20)->nullable()->after('primary_contact_number')
                ->comment('Secondary/emergency contact number');

            // ── Social Media ──
            $table->string('social_facebook', 255)->nullable()->after('institution_affiliated')
                ->comment('Facebook profile URL or name');
            $table->string('social_linkedin', 255)->nullable()->after('social_facebook')
                ->comment('LinkedIn profile URL or handle');
            $table->text('social_other')->nullable()->after('social_linkedin')
                ->comment('Other social media or contact handles (freeform)');

            // ── Additional Adviser Roles ──
            $table->foreignId('registration_adviser_id')->nullable()->after('adviser_id')
                ->constrained('faculty')->nullOnDelete()
                ->comment('Faculty who signs registration forms');
            $table->foreignId('temporary_adviser_id')->nullable()->after('registration_adviser_id')
                ->constrained('faculty')->nullOnDelete()
                ->comment('Adviser assigned at admission before thesis adviser');

            // ── Applicant Status ──
            $table->string('applicant_status', 30)->nullable()->after('student_status')
                ->comment('Admission classification: regular, probationary, denied, change-of-program, deferred');
        });

        // ── Update student_status enum ──
        // MySQL requires ALTER COLUMN to change enum values.
        // Rename loa-approved → leave-of-absence-approved, add absent-without-official-leave and inactive.
        DB::statement("ALTER TABLE students MODIFY COLUMN student_status ENUM(
            'active',
            'on-leave',
            'leave-of-absence-approved',
            'absent-without-official-leave',
            'graduated',
            'dismissed',
            'dropped',
            'withdrawn',
            'inactive'
        ) DEFAULT 'active'");

        // Migrate existing loa-approved rows to the new value
        DB::table('students')
            ->where('student_status', 'loa-approved')
            ->update(['student_status' => 'leave-of-absence-approved']);
    }

    public function down(): void
    {
        // Revert student_status enum
        DB::table('students')
            ->where('student_status', 'leave-of-absence-approved')
            ->update(['student_status' => 'loa-approved']);

        DB::statement("ALTER TABLE students MODIFY COLUMN student_status ENUM(
            'active', 'on-leave', 'graduated', 'dismissed',
            'dropped', 'withdrawn', 'loa-approved'
        ) DEFAULT 'active'");

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('temporary_adviser_id');
            $table->dropConstrainedForeignId('registration_adviser_id');
            $table->dropColumn([
                'up_email',
                'alternative_email',
                'primary_contact_number',
                'alternative_contact_number',
                'social_facebook',
                'social_linkedin',
                'social_other',
                'applicant_status',
            ]);
        });
    }
};
