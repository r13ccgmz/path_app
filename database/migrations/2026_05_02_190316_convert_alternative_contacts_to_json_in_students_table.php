<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Wrap existing text data into a JSON array, skipping already formatted JSON or empty strings
        \Illuminate\Support\Facades\DB::statement('UPDATE students SET alternative_email = JSON_ARRAY(alternative_email) WHERE alternative_email IS NOT NULL AND alternative_email != "" AND alternative_email NOT LIKE "[%"');
        \Illuminate\Support\Facades\DB::statement('UPDATE students SET alternative_contact_number = JSON_ARRAY(alternative_contact_number) WHERE alternative_contact_number IS NOT NULL AND alternative_contact_number != "" AND alternative_contact_number NOT LIKE "[%"');

        Schema::table('students', function (Blueprint $table) {
            $table->json('alternative_email')->nullable()->change();
            $table->json('alternative_contact_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('alternative_email', 255)->nullable()->change();
            $table->string('alternative_contact_number', 20)->nullable()->change();
        });
    }
};
