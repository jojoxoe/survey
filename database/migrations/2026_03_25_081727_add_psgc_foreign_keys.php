<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('cascade');
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
        });
    }
};
