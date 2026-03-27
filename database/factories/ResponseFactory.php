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
            'respondent_hash' => hash('sha256', fake()->uuid()),
            'respondent_name' => fake()->optional()->name(),
            'respondent_gender' => fake()->randomElement(['Male', 'Female', 'Prefer not to say']),
            'region_code' => null,
            'region_name' => null,
            'province_code' => null,
            'province_name' => null,
            'city_municipality_code' => null,
            'city_municipality_name' => null,
            'barangay_code' => null,
            'barangay_name' => null,
            'completed_at' => now(),
        ];
    }
}
