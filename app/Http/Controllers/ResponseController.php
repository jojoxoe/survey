<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Response;
use App\Models\ResponseAnswer;
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

        if (!$survey) {
            return back()->withErrors(['code' => 'Survey not found. Please check the code.']);
        }

        return redirect()->route('survey.respond', $survey->slug);
    }

    protected function showSurvey(Survey $survey): View|RedirectResponse
    {
        if (!$survey->isAccessible()) {
            return view('respond.closed', compact('survey'));
        }

        $survey->load('questions.options');

        return view('respond.show', compact('survey'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $survey = Survey::where('slug', $slug)->firstOrFail();

        if (!$survey->isAccessible()) {
            return redirect()->route('survey.respond', $slug)
                ->withErrors(['survey' => 'This survey is no longer accepting responses.']);
        }

        $survey->load('questions.options');

        // Build validation rules dynamically
        $rules = [
            'respondent_name'     => ['nullable', 'string', 'max:255'],
            'respondent_gender'   => ['required', 'in:Male,Female,Prefer not to say'],
            'respondent_region'   => ['required', 'string', 'max:255'],
            'respondent_province' => ['required', 'string', 'max:255'],
            'respondent_city'     => ['required', 'string', 'max:255'],
            'respondent_barangay' => ['required', 'string', 'max:255'],
        ];
        foreach ($survey->questions as $question) {
            $key = 'answers.' . $question->id;

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
                        $rules[$key . '.*'] = ['exists:question_options,id'];
                    } else {
                        $rules[$key][] = 'exists:question_options,id';
                    }
                    break;
                case 'ranking':
                    $rules[$key] = $question->is_required ? ['required', 'array', 'min:1'] : ['nullable', 'array'];
                    $rules[$key . '.*'] = ['exists:question_options,id'];
                    break;
                case 'rating':
                    $maxRating = $question->settings['max_rating'] ?? 5;
                    $rules[$key][] = 'integer';
                    $rules[$key][] = 'min:1';
                    $rules[$key][] = 'max:' . $maxRating;
                    break;
                case 'open_ended':
                    $rules[$key][] = 'string';
                    $rules[$key][] = 'max:5000';
                    break;
            }
        }

        $validated = $request->validate($rules);
        $answers = $validated['answers'] ?? [];

        // Check for duplicate submission (hashed IP per survey)
        $ipHash = hash('sha256', $request->ip() . $survey->id . config('app.key'));
        $existing = Response::where('survey_id', $survey->id)
            ->where('respondent_hash', $ipHash)
            ->exists();

        if ($existing) {
            return redirect()->route('survey.respond', $slug)
                ->withErrors(['survey' => 'You have already submitted a response to this survey.']);
        }

        DB::transaction(function () use ($survey, $answers, $ipHash, $validated) {
            $response = $survey->responses()->create([
                'respondent_hash'     => $ipHash,
                'respondent_name'     => $validated['respondent_name'] ?? null,
                'respondent_gender'   => $validated['respondent_gender'],
                'respondent_region'   => $validated['respondent_region'],
                'respondent_province' => $validated['respondent_province'],
                'respondent_city'     => $validated['respondent_city'],
                'respondent_barangay' => $validated['respondent_barangay'],
                'completed_at'        => now(),
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
