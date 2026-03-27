<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyResponseRequest;
use App\Models\Response;
use App\Models\Survey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResponseController extends Controller
{
    public function showBySlug(string $slug): View|RedirectResponse
    {
        $survey = Survey::where('slug', $slug)->firstOrFail();

        return $this->showSurvey($survey);
    }

    public function showByCode(string $code): View|RedirectResponse
    {
        $survey = Survey::where('access_code', $code)->firstOrFail();

        return $this->showSurvey($survey);
    }

    public function lookupCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:8'],
        ]);

        $survey = Survey::where('access_code', strtoupper($request->code))->first();

        if (! $survey) {
            return back()->withErrors(['code' => 'Survey not found. Please check the code.']);
        }

        return redirect()->route('survey.respond', $survey->slug);
    }

    protected function showSurvey(Survey $survey): View|RedirectResponse
    {
        if (! $survey->isAccessible()) {
            return view('respond.closed', compact('survey'));
        }

        $survey->load('questions.options');

        return view('respond.show', compact('survey'));
    }

    public function store(StoreSurveyResponseRequest $request, string $slug): RedirectResponse
    {
        $survey = $request->survey();

        if (! $survey->isAccessible()) {
            return redirect()->route('survey.respond', $slug)
                ->withErrors(['survey' => 'This survey is no longer accepting responses.']);
        }

        $validated = $request->validated();
        $answers = $validated['answers'] ?? [];

        // Check for duplicate submission (hashed IP per survey)
        $ipHash = hash('sha256', $request->ip().$survey->id.config('app.key'));
        $existing = Response::where('survey_id', $survey->id)
            ->where('respondent_hash', $ipHash)
            ->exists();

        if ($existing) {
            return redirect()->route('survey.respond', $slug)
                ->withErrors(['survey' => 'You have already submitted a response to this survey.']);
        }

        DB::transaction(function () use ($survey, $answers, $ipHash, $validated) {
            $response = $survey->responses()->create([
                'respondent_hash' => $ipHash,
                'respondent_name' => $validated['respondent_name'] ?? null,
                'respondent_gender' => $validated['respondent_gender'],
                'region_code' => $validated['region_code'],
                'region_name' => $validated['region_name'],
                'province_code' => $validated['province_code'],
                'province_name' => $validated['province_name'],
                'city_municipality_code' => $validated['city_municipality_code'],
                'city_municipality_name' => $validated['city_municipality_name'],
                'barangay_code' => $validated['barangay_code'],
                'barangay_name' => $validated['barangay_name'],
                'completed_at' => now(),
            ]);

            foreach ($survey->questions as $question) {
                $answer = $answers[$question->id] ?? null;

                if ($answer === null) {
                    continue;
                }

                switch ($question->type) {
                    case 'true_false':
                    case 'open_ended':
                        $response->answers()->create([
                            'question_id' => $question->id,
                            'value' => $answer,
                        ]);
                        break;

                    case 'rating':
                        $response->answers()->create([
                            'question_id' => $question->id,
                            'value' => (string) $answer,
                        ]);
                        break;

                    case 'multiple_choice':
                        $allowMultiple = $question->settings['allow_multiple'] ?? false;
                        if ($allowMultiple && is_array($answer)) {
                            foreach ($answer as $optionId) {
                                $response->answers()->create([
                                    'question_id' => $question->id,
                                    'question_option_id' => $optionId,
                                ]);
                            }
                        } else {
                            $response->answers()->create([
                                'question_id' => $question->id,
                                'question_option_id' => $answer,
                            ]);
                        }
                        break;

                    case 'ranking':
                        if (is_array($answer)) {
                            foreach ($answer as $rank => $optionId) {
                                $response->answers()->create([
                                    'question_id' => $question->id,
                                    'question_option_id' => $optionId,
                                    'value' => (string) ($rank + 1),
                                ]);
                            }
                        }
                        break;
                }
            }
        });

        return redirect()->route('survey.thankyou', $slug);
    }

    public function thankyou(string $slug): View
    {
        $survey = Survey::where('slug', $slug)->firstOrFail();

        return view('respond.thankyou', compact('survey'));
    }
}
