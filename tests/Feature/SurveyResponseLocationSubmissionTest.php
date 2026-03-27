<?php

namespace Tests\Feature;

use App\Models\Response;
use App\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SurveyResponseLocationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_response_submission_persists_psgc_location_codes_and_names(): void
    {
        $survey = Survey::factory()->published()->create();

        Http::fake([
            'https://psgc.cloud/api/v2/regions' => Http::response([
                ['code' => '130000000', 'name' => 'National Capital Region'],
            ], 200),
            'https://psgc.cloud/api/v2/regions/130000000/provinces' => Http::response([
                ['code' => '137400000', 'name' => 'Metro Manila'],
            ], 200),
            'https://psgc.cloud/api/v2/provinces/137400000/cities-municipalities' => Http::response([
                ['code' => '137404000', 'name' => 'City of Manila'],
            ], 200),
            'https://psgc.cloud/api/v2/cities-municipalities/137404000/barangays' => Http::response([
                ['code' => '137404001', 'name' => 'Barangay 1'],
            ], 200),
        ]);

        $response = $this->post(route('survey.submit', $survey->slug), [
            'respondent_name' => 'Juan Dela Cruz',
            'respondent_gender' => 'Male',
            'region_code' => '130000000',
            'region_name' => 'National Capital Region',
            'province_code' => '137400000',
            'province_name' => 'Metro Manila',
            'city_municipality_code' => '137404000',
            'city_municipality_name' => 'City of Manila',
            'barangay_code' => '137404001',
            'barangay_name' => 'Barangay 1',
        ]);

        $response->assertRedirect(route('survey.thankyou', $survey->slug));

        $this->assertDatabaseHas('responses', [
            'survey_id' => $survey->id,
            'respondent_name' => 'Juan Dela Cruz',
            'respondent_gender' => 'Male',
            'region_code' => '130000000',
            'region_name' => 'National Capital Region',
            'province_code' => '137400000',
            'province_name' => 'Metro Manila',
            'city_municipality_code' => '137404000',
            'city_municipality_name' => 'City of Manila',
            'barangay_code' => '137404001',
            'barangay_name' => 'Barangay 1',
        ]);

        $this->assertSame(1, Response::count());
    }

    public function test_response_submission_rejects_invalid_location_hierarchy(): void
    {
        $survey = Survey::factory()->published()->create();

        Http::fake([
            'https://psgc.cloud/api/v2/regions' => Http::response([
                ['code' => '130000000', 'name' => 'National Capital Region'],
            ], 200),
            'https://psgc.cloud/api/v2/regions/130000000/provinces' => Http::response([
                ['code' => '010280000', 'name' => 'Ilocos Norte'],
            ], 200),
        ]);

        $response = $this->from(route('survey.respond', $survey->slug))
            ->post(route('survey.submit', $survey->slug), [
                'respondent_gender' => 'Female',
                'region_code' => '130000000',
                'region_name' => 'National Capital Region',
                'province_code' => '137400000',
                'province_name' => 'Metro Manila',
                'city_municipality_code' => '137404000',
                'city_municipality_name' => 'City of Manila',
                'barangay_code' => '137404001',
                'barangay_name' => 'Barangay 1',
            ]);

        $response->assertRedirect(route('survey.respond', $survey->slug));
        $response->assertSessionHasErrors('province_code');

        $this->assertDatabaseCount('responses', 0);
    }
}
