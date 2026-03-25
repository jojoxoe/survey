<?php

namespace Database\Factories;

use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Response>
 */
class ResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'respondent_hash' => hash('sha256', \fake()->uuid()),
            'respondent_name' => \fake()->optional()->name(),
            'respondent_gender' => \fake()->randomElement(['Male', 'Female', 'Prefer not to say']),
            'respondent_region' => \fake()->city(),
            'respondent_province' => \fake()->state(),
            'respondent_city' => \fake()->city(),
            'respondent_barangay' => 'Barangay '.\fake()->numberBetween(1, 99),
            'completed_at' => now(),
        ];
    }
}
