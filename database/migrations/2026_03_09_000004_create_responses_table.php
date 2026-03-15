<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('respondent_hash', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('survey_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responses');
    }
};
