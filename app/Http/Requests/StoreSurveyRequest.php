<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', 'in:true_false,multiple_choice,ranking,rating,open_ended'],
            'questions.*.title' => ['required', 'string', 'max:500'],
            'questions.*.description' => ['nullable', 'string', 'max:500'],
            'questions.*.is_required' => ['boolean'],
            'questions.*.settings' => ['nullable', 'array'],
            'questions.*.settings.max_rating' => ['nullable', 'integer', 'min:2', 'max:10'],
            'questions.*.settings.allow_multiple' => ['nullable', 'boolean'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.required' => 'You must add at least one question.',
            'questions.min' => 'You must add at least one question.',
            'questions.*.title.required' => 'Each question must have a title.',
            'questions.*.options.*.required' => 'Each option must have a label.',
        ];
    }
}
