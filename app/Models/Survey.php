<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Survey $survey) {
            if (empty($survey->access_code)) {
                $survey->access_code = self::generateUniqueCode();
            }
            if (empty($survey->slug)) {
                $survey->slug = self::generateUniqueSlug($survey->title);
            }
        });

        static::updating(function (Survey $survey) {
            // Regenerate slug if title has changed
            if ($survey->isDirty('title')) {
                $survey->slug = self::generateUniqueSlugForUpdate($survey->title, $survey->id);
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('access_code', $code)->exists());

        return $code;
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }

    public static function generateUniqueSlugForUpdate(string $title, int $surveyId): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (self::where('slug', $slug)->where('id', '!=', $surveyId)->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return $this->isPublished() && ! $this->isExpired();
    }

    public function getShareUrlAttribute(): string
    {
        return url('/s/'.$this->slug);
    }
}
