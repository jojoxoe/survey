<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultsController extends Controller
{
    public function show(Survey $survey): View
    {
        $this->authorize('view', $survey);

        $survey->load('questions.options', 'questions.answers.option', 'responses');

        $analytics = $this->buildAnalytics($survey);
        $demographicsRows = $this->buildDemographicsRows($survey);

        return view('surveys.results', compact('survey', 'analytics', 'demographicsRows'));
    }

    public function export(Survey $survey): StreamedResponse
    {
        $this->authorize('view', $survey);

        $survey->load('questions.options', 'responses.answers');

        $filename = 'survey-' . $survey->slug . '-responses.csv';

        return response()->streamDownload(function () use ($survey) {
            $handle = fopen('php://output', 'w');

            // Header row
            $headers = ['Response #', 'Name', 'Gender', 'Region', 'Province', 'City/Municipality', 'Barangay', 'Submitted At'];
            foreach ($survey->questions as $question) {
                $headers[] = $question->title;
            }
            fputcsv($handle, $headers);

            // Data rows
            foreach ($survey->responses as $index => $response) {
                $row = [
                    $index + 1,
                    $response->respondent_name ?? '',
                    $response->respondent_gender ?? '',
                    $response->respondent_region ?? '',
                    $response->respondent_province ?? '',
                    $response->respondent_city ?? '',
                    $response->respondent_barangay ?? '',
                    $response->completed_at?->format('Y-m-d H:i:s'),
                ];

                foreach ($survey->questions as $question) {
                    $questionAnswers = $response->answers->where('question_id', $question->id);

                    switch ($question->type) {
                        case 'true_false':
                        case 'open_ended':
                        case 'rating':
                            $row[] = $questionAnswers->first()?->value ?? '';
                            break;

                        case 'multiple_choice':
                            $labels = $questionAnswers->map(function ($a) {
                                return $a->option?->label ?? '';
                            })->filter()->implode(', ');
                            $row[] = $labels;
                            break;

                        case 'ranking':
                            $ranked = $questionAnswers->sortBy('value')->map(function ($a) {
                                return $a->value . '. ' . ($a->option?->label ?? '');
                            })->implode('; ');
                            $row[] = $ranked;
                            break;

                        default:
                            $row[] = '';
                    }
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function buildAnalytics(Survey $survey): array
    {
        $analytics = [];

        foreach ($survey->questions as $question) {
            $answers = $question->answers;
            $totalResponses = $survey->responses->count();

            $data = [
                'question' => $question,
                'total_answers' => $answers->count(),
                'total_responses' => $totalResponses,
            ];

            switch ($question->type) {
                case 'true_false':
                    $trueCount = $answers->where('value', 'true')->count();
                    $falseCount = $answers->where('value', 'false')->count();
                    $data['chart'] = [
                        'labels' => ['True', 'False'],
                        'values' => [$trueCount, $falseCount],
                    ];
                    break;

                case 'multiple_choice':
                    $labels = [];
                    $values = [];
                    foreach ($question->options as $option) {
                        $labels[] = $option->label;
                        $values[] = $answers->where('question_option_id', $option->id)->count();
                    }
                    $data['chart'] = [
                        'labels' => $labels,
                        'values' => $values,
                    ];
                    break;

                case 'ranking':
                    $rankings = [];
                    foreach ($question->options as $option) {
                        $optionAnswers = $answers->where('question_option_id', $option->id);
                        $avgRank = $optionAnswers->count() > 0
                            ? round($optionAnswers->avg('value'), 2)
                            : null;
                        $rankings[] = [
                            'label' => $option->label,
                            'avg_rank' => $avgRank,
                        ];
                    }
                    usort($rankings, fn($a, $b) => ($a['avg_rank'] ?? 999) <=> ($b['avg_rank'] ?? 999));
                    $data['rankings'] = $rankings;
                    break;

                case 'rating':
                    $maxRating = $question->settings['max_rating'] ?? 5;
                    $avg = $answers->count() > 0 ? round($answers->avg('value'), 2) : 0;
                    $distribution = [];
                    for ($i = 1; $i <= $maxRating; $i++) {
                        $distribution[$i] = $answers->where('value', (string) $i)->count();
                    }
                    $data['average'] = $avg;
                    $data['chart'] = [
                        'labels' => array_map(fn($i) => (string) $i, range(1, $maxRating)),
                        'values' => array_values($distribution),
                    ];
                    break;

                case 'open_ended':
                    $data['text_responses'] = $answers->pluck('value')->filter()->values()->toArray();
                    break;
            }

            $analytics[] = $data;
        }

        return $analytics;
    }

    protected function buildDemographicsRows(Survey $survey): array
    {
        return $survey->responses
            ->sortByDesc(fn ($response) => $response->completed_at ?? $response->created_at)
            ->values()
            ->map(function ($response) {
                $locationParts = [
                    $response->respondent_region,
                    $response->respondent_province,
                    $response->respondent_city,
                    $response->respondent_barangay,
                ];

                return [
                    'respondent' => filled($response->respondent_name) ? $response->respondent_name : 'Anonymous',
                    'gender' => $response->respondent_gender,
                    'location' => implode(' / ', array_filter($locationParts, fn ($part) => filled($part))),
                    'submitted_at' => $response->completed_at?->format('Y-m-d H:i:s') ?? '-',
                ];
            })
            ->toArray();
    }
}
