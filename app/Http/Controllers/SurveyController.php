<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function index(Request $request): View
    {
        $surveys = $request->user()->surveys()->latest()->get();

        return view('dashboard', compact('surveys'));
    }

    public function create(): View
    {
        return view('surveys.create');
    }

    public function store(StoreSurveyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            $survey = $request->user()->surveys()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['questions'] as $index => $questionData) {
                $question = $survey->questions()->create([
                    'type' => $questionData['type'],
                    'title' => $questionData['title'],
                    'description' => $questionData['description'] ?? null,
                    'is_required' => $questionData['is_required'] ?? true,
                    'order' => $index,
                    'settings' => $questionData['settings'] ?? null,
                ]);

                if (!empty($questionData['options'])) {
                    foreach ($questionData['options'] as $optIndex => $label) {
                        $question->options()->create([
                            'label' => $label,
                            'order' => $optIndex,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('dashboard')->with('success', 'Survey created successfully!');
    }

    public function edit(Survey $survey): View
    {
        $this->authorize('update', $survey);

        $survey->load('questions.options');

        $existingQuestions = $survey->questions->map(function ($q) {
            return [
                'type'        => $q->type,
                'title'       => $q->title,
                'description' => $q->description,
                'is_required' => $q->is_required,
                'settings'    => $q->settings ?? [],
                'options'     => $q->options->pluck('label')->toArray(),
            ];
        })->values();

        return view('surveys.edit', compact('survey', 'existingQuestions'));
    }

    public function update(StoreSurveyRequest $request, Survey $survey): RedirectResponse
    {
        $this->authorize('update', $survey);

        $validated = $request->validated();

        DB::transaction(function () use ($survey, $validated) {
            $survey->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            // Delete old questions and recreate
            $survey->questions()->delete();

            foreach ($validated['questions'] as $index => $questionData) {
                $question = $survey->questions()->create([
                    'type' => $questionData['type'],
                    'title' => $questionData['title'],
                    'description' => $questionData['description'] ?? null,
                    'is_required' => $questionData['is_required'] ?? true,
                    'order' => $index,
                    'settings' => $questionData['settings'] ?? null,
                ]);

                if (!empty($questionData['options'])) {
                    foreach ($questionData['options'] as $optIndex => $label) {
                        $question->options()->create([
                            'label' => $label,
                            'order' => $optIndex,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('dashboard')->with('success', 'Survey updated successfully!');
    }

    public function destroy(Survey $survey): RedirectResponse
    {
        $this->authorize('delete', $survey);

        if ($survey->status === 'closed') {
            $password = (string) request()->input('password', '');
            if (!Hash::check($password, request()->user()->password)) {
                return redirect()->route('dashboard')
                    ->with('delete_error', 'Incorrect password. Please try again.')
                    ->with('delete_survey_url', route('surveys.destroy', $survey));
            }
        }

        $survey->delete();

        return redirect()->route('dashboard')->with('success', 'Survey deleted.');
    }

    public function publish(Survey $survey): RedirectResponse
    {
        $this->authorize('update', $survey);

        $survey->update(['status' => 'published']);

        return back()->with('success', 'Survey published! Share the link or code with respondents.');
    }

    public function close(Survey $survey): RedirectResponse
    {
        $this->authorize('update', $survey);

        $survey->update(['status' => 'closed']);

        return back()->with('success', 'Survey closed.');
    }

    public function reopen(Survey $survey): RedirectResponse
    {
        $this->authorize('update', $survey);

        $survey->update(['status' => 'published']);

        return back()->with('success', 'Survey reopened. Respondents with the code can answer again.');
    }

    public function duplicate(Survey $survey): RedirectResponse
    {
        $this->authorize('view', $survey);

        DB::transaction(function () use ($survey) {
            $newSurvey = $survey->user->surveys()->create([
                'title' => $survey->title . ' (Copy)',
                'description' => $survey->description,
            ]);

            foreach ($survey->questions()->with('options')->get() as $question) {
                $newQuestion = $newSurvey->questions()->create([
                    'type' => $question->type,
                    'title' => $question->title,
                    'description' => $question->description,
                    'is_required' => $question->is_required,
                    'order' => $question->order,
                    'settings' => $question->settings,
                ]);

                foreach ($question->options as $option) {
                    $newQuestion->options()->create([
                        'label' => $option->label,
                        'order' => $option->order,
                    ]);
                }
            }
        });

        return redirect()->route('dashboard')->with('success', 'Survey duplicated!');
    }
}
