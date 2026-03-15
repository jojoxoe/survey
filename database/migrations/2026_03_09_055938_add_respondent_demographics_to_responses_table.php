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
        Schema::table('responses', function (Blueprint $table) {
            $table->string('respondent_name')->nullable()->after('respondent_hash');
            $table->string('respondent_gender')->after('respondent_name');
            $table->string('respondent_region')->after('respondent_gender');
            $table->string('respondent_province')->after('respondent_region');
            $table->string('respondent_city')->after('respondent_province');
            $table->string('respondent_barangay')->after('respondent_city');
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn([
                'respondent_name',
                'respondent_gender',
                'respondent_region',
                'respondent_province',
                'respondent_city',
                'respondent_barangay',
            ]);
        });
    }
};
