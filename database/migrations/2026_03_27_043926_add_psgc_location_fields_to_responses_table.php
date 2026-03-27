<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->string('region_code', 20)->nullable()->after('respondent_gender');
            $table->string('region_name', 255)->nullable()->after('region_code');
            $table->string('province_code', 20)->nullable()->after('region_name');
            $table->string('province_name', 255)->nullable()->after('province_code');
            $table->string('city_municipality_code', 20)->nullable()->after('province_name');
            $table->string('city_municipality_name', 255)->nullable()->after('city_municipality_code');
            $table->string('barangay_code', 20)->nullable()->after('city_municipality_name');
            $table->string('barangay_name', 255)->nullable()->after('barangay_code');

            $table->index('region_code');
            $table->index('province_code');
            $table->index('city_municipality_code');
            $table->index('barangay_code');
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropIndex(['region_code']);
            $table->dropIndex(['province_code']);
            $table->dropIndex(['city_municipality_code']);
            $table->dropIndex(['barangay_code']);

            $table->dropColumn([
                'region_code',
                'region_name',
                'province_code',
                'province_name',
                'city_municipality_code',
                'city_municipality_name',
                'barangay_code',
                'barangay_name',
            ]);
        });
    }
};
