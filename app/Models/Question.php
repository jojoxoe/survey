<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'survey_id',
        'type',
        'title',
        'description',
        'is_required',
        'order',
        'settings',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'settings' => 'array',
    ];

    public const TYPES = [
        'true_false' => 'True or False',
        'multiple_choice' => 'Multiple Choice',
        'ranking' => 'Ranking',
        'rating' => 'Rating',
        'open_ended' => 'Open Ended',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ResponseAnswer::class);
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
