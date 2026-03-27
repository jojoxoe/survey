<?php

namespace Tests\Feature;

use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyResultsDemographicsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure results include respondent identity fallback, gender, and location.
     */
    public function test_results_show_name_gender_and_location_with_anonymous_fallback(): void
    {
        $owner = User::factory()->create();
        $survey = Survey::factory()->for($owner)->published()->create();

        Response::factory()->for($survey)->create([
            'respondent_name' => null,
            'respondent_gender' => 'Female',
            'region_name' => 'Region I (Ilocos Region)',
            'province_name' => 'Ilocos Norte',
            'city_municipality_name' => 'Laoag City',
            'barangay_name' => 'Barangay 1',
            'completed_at' => now()->subMinute(),
        ]);

        Response::factory()->for($survey)->create([
            'respondent_name' => 'Juan Dela Cruz',
            'respondent_gender' => 'Male',
            'region_name' => 'Region IV-A (CALABARZON)',
            'province_name' => 'Batangas',
            'city_municipality_name' => 'Padre Garcia',
            'barangay_name' => 'Poblacion',
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($owner)
            ->get(route('surveys.results', $survey));

        $response->assertOk();
        $response->assertSeeText('Respondent Details');
        $response->assertSeeText('Anonymous');
        $response->assertSeeText('Juan Dela Cruz');
        $response->assertSeeText('Female');
        $response->assertSeeText('Male');
        $response->assertSeeText('Barangay 1, Laoag City, Ilocos Norte, Region I (Ilocos Region)');
        $response->assertSeeText('Poblacion, Padre Garcia, Batangas, Region IV-A (CALABARZON)');
    }

    /**
     * Ensure non-owners cannot view another survey's results.
     */
    public function test_non_owner_cannot_view_results_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $survey = Survey::factory()->for($owner)->published()->create();

        $this->actingAs($otherUser)
            ->get(route('surveys.results', $survey))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('surveys.results', $survey))
            ->assertOk();
    }
}
