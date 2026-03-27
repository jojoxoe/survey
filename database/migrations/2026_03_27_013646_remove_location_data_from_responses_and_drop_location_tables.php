<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('responses')) {
            Schema::table('responses', function (Blueprint $table) {
                $columns = [
                    'respondent_region',
                    'respondent_province',
                    'respondent_city',
                    'respondent_barangay',
                ];

                $existingColumns = array_values(array_filter(
                    $columns,
                    fn (string $column): bool => Schema::hasColumn('responses', $column)
                ));

                if ($existingColumns !== []) {
                    $table->dropColumn($existingColumns);
                }
            });
        }

        Schema::dropIfExists('barangays');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('regions');
    }

    public function down(): void
    {
        if (Schema::hasTable('responses')) {
            Schema::table('responses', function (Blueprint $table) {
                if (! Schema::hasColumn('responses', 'respondent_region')) {
                    $table->string('respondent_region')->nullable()->after('respondent_gender');
                }

                if (! Schema::hasColumn('responses', 'respondent_province')) {
                    $table->string('respondent_province')->nullable()->after('respondent_region');
                }

                if (! Schema::hasColumn('responses', 'respondent_city')) {
                    $table->string('respondent_city')->nullable()->after('respondent_province');
                }

                if (! Schema::hasColumn('responses', 'respondent_barangay')) {
                    $table->string('respondent_barangay')->nullable()->after('respondent_city');
                }
            });
        }

        if (! Schema::hasTable('regions')) {
            Schema::create('regions', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('provinces')) {
            Schema::create('provinces', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('barangays')) {
            Schema::create('barangays', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }
};
