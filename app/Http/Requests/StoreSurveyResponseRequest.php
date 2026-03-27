<?php

namespace App\Http\Requests;

use App\Models\Survey;
use App\Services\PsgcLocationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Throwable;

class StoreSurveyResponseRequest extends FormRequest
{
    protected ?Survey $survey = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'respondent_name' => ['nullable', 'string', 'max:255'],
            'respondent_gender' => ['required', 'in:Male,Female,Prefer not to say'],
            'region_code' => ['required', 'string', 'max:20'],
            'region_name' => ['required', 'string', 'max:255'],
            'province_code' => ['required', 'string', 'max:20'],
            'province_name' => ['required', 'string', 'max:255'],
            'city_municipality_code' => ['required', 'string', 'max:20'],
            'city_municipality_name' => ['required', 'string', 'max:255'],
            'barangay_code' => ['required', 'string', 'max:20'],
            'barangay_name' => ['required', 'string', 'max:255'],
        ];

        foreach ($this->survey()->questions as $question) {
            $key = 'answers.'.$question->id;

            if ($question->is_required) {
                $rules[$key] = ['required'];
            } else {
                $rules[$key] = ['nullable'];
            }

            switch ($question->type) {
                case 'true_false':
                    $rules[$key][] = 'in:true,false';
                    break;

                case 'multiple_choice':
                    $allowMultiple = $question->settings['allow_multiple'] ?? false;
                    if ($allowMultiple) {
                        $rules[$key] = $question->is_required ? ['required', 'array', 'min:1'] : ['nullable', 'array'];
                        $rules[$key.'.*'] = ['exists:question_options,id'];
                    } else {
                        $rules[$key][] = 'exists:question_options,id';
                    }
                    break;

                case 'ranking':
                    $rules[$key] = $question->is_required ? ['required', 'array', 'min:1'] : ['nullable', 'array'];
                    $rules[$key.'.*'] = ['exists:question_options,id'];
                    break;

                case 'rating':
                    $maxRating = $question->settings['max_rating'] ?? 5;
                    $rules[$key][] = 'integer';
                    $rules[$key][] = 'min:1';
                    $rules[$key][] = 'max:'.$maxRating;
                    break;

                case 'open_ended':
                    $rules[$key][] = 'string';
                    $rules[$key][] = 'max:5000';
                    break;
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'region_code.required' => 'Please select your region.',
            'province_code.required' => 'Please select your province.',
            'city_municipality_code.required' => 'Please select your city or municipality.',
            'barangay_code.required' => 'Please select your barangay.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = app(PsgcLocationService::class);

            try {
                $this->validateHierarchyAndNames($validator, $service);
            } catch (Throwable $exception) {
                report($exception);
                $validator->errors()->add('region_code', 'Location verification is temporarily unavailable. Please try submitting again.');
            }
        });
    }

    public function survey(): Survey
    {
        if ($this->survey instanceof Survey) {
            return $this->survey;
        }

        $slug = (string) $this->route('slug');

        $this->survey = Survey::where('slug', $slug)
            ->with('questions.options')
            ->firstOrFail();

        return $this->survey;
    }

    protected function validateHierarchyAndNames(Validator $validator, PsgcLocationService $service): void
    {
        $region = $this->findLocationByCode($service->regions(), (string) $this->input('region_code'));
        if ($region === null) {
            $validator->errors()->add('region_code', 'Selected region is invalid.');

            return;
        }

        if (! $this->namesMatch($region['name'], (string) $this->input('region_name'))) {
            $validator->errors()->add('region_name', 'Selected region name does not match the selected region.');

            return;
        }

        $province = $this->findLocationByCode(
            $service->provinces((string) $this->input('region_code')),
            (string) $this->input('province_code')
        );
        if ($province === null) {
            $validator->errors()->add('province_code', 'Selected province is invalid for the selected region.');

            return;
        }

        if (! $this->namesMatch($province['name'], (string) $this->input('province_name'))) {
            $validator->errors()->add('province_name', 'Selected province name does not match the selected province.');

            return;
        }

        $cityMunicipality = $this->findLocationByCode(
            $service->citiesMunicipalities((string) $this->input('province_code')),
            (string) $this->input('city_municipality_code')
        );
        if ($cityMunicipality === null) {
            $validator->errors()->add('city_municipality_code', 'Selected city or municipality is invalid for the selected province.');

            return;
        }

        if (! $this->namesMatch($cityMunicipality['name'], (string) $this->input('city_municipality_name'))) {
            $validator->errors()->add('city_municipality_name', 'Selected city or municipality name does not match the selected value.');

            return;
        }

        $barangay = $this->findLocationByCode(
            $service->barangays((string) $this->input('city_municipality_code')),
            (string) $this->input('barangay_code')
        );
        if ($barangay === null) {
            $validator->errors()->add('barangay_code', 'Selected barangay is invalid for the selected city or municipality.');

            return;
        }

        if (! $this->namesMatch($barangay['name'], (string) $this->input('barangay_name'))) {
            $validator->errors()->add('barangay_name', 'Selected barangay name does not match the selected barangay.');
        }
    }

    protected function findLocationByCode(array $locations, string $code): ?array
    {
        foreach ($locations as $location) {
            if (($location['code'] ?? null) === $code) {
                return $location;
            }
        }

        return null;
    }

    protected function namesMatch(string $expected, string $actual): bool
    {
        return Str::lower(trim($expected)) === Str::lower(trim($actual));
    }
}
