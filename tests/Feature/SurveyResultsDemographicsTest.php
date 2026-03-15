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
            'respondent_region' => 'Region I',
            'respondent_province' => 'Ilocos Norte',
            'respondent_city' => 'Laoag City',
            'respondent_barangay' => 'Barangay 1',
            'completed_at' => now()->subMinute(),
        ]);

        Response::factory()->for($survey)->create([
            'respondent_name' => 'Juan Dela Cruz',
            'respondent_gender' => 'Male',
            'respondent_region' => 'Region III',
            'respondent_province' => 'Pampanga',
            'respondent_city' => 'San Fernando',
            'respondent_barangay' => 'Barangay 2',
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
        $response->assertSeeText('Region I / Ilocos Norte / Laoag City / Barangay 1');
        $response->assertSeeText('Region III / Pampanga / San Fernando / Barangay 2');
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
