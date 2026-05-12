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
        \Illuminate\Support\Facades\DB::statement('UPDATE students SET address = JSON_ARRAY(address) WHERE address IS NOT NULL AND address != "" AND address NOT LIKE "[%"');
        \Illuminate\Support\Facades\DB::statement('UPDATE students SET social_other = JSON_ARRAY(social_other) WHERE social_other IS NOT NULL AND social_other != "" AND social_other NOT LIKE "[%"');
        \Illuminate\Support\Facades\DB::statement('UPDATE students SET institution_affiliated = JSON_ARRAY(institution_affiliated) WHERE institution_affiliated IS NOT NULL AND institution_affiliated != "" AND institution_affiliated NOT LIKE "[%"');
        \Illuminate\Support\Facades\DB::statement('UPDATE students SET nationality = JSON_ARRAY(nationality) WHERE nationality IS NOT NULL AND nationality != "" AND nationality NOT LIKE "[%"');

        Schema::table('students', function (Blueprint $table) {
            $table->json('address')->nullable()->change();
            $table->json('social_other')->nullable()->change();
            $table->json('institution_affiliated')->nullable()->change();
            $table->json('nationality')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->text('address')->nullable()->change();
            $table->string('social_other', 255)->nullable()->change();
            $table->string('institution_affiliated', 255)->nullable()->change();
            $table->string('nationality', 50)->nullable()->change();
        });
    }
};
