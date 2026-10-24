<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['slug', 'title', 'excerpt', 'body', 'meta_description', 'cover_media_id', 'is_published', 'published_at'])]
class BlogPost extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'excerpt' => 'array',
            'body' => 'array',
            'meta_description' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'cover_media_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Published and not scheduled for later. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isVisible(): bool
    {
        return $this->is_published && $this->published_at !== null && $this->published_at->lte(now());
    }

    public function url(): string
    {
        return route('blog.show', $this->slug);
    }

    /** Approximate reading time of the current locale's body. */
    public function readingMinutes(): int
    {
        // str_word_count() splits on Turkish letters; count whitespace-separated words instead.
        $words = preg_split('/\s+/u', trim((string) $this->translate('body')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) round(count($words) / 200));
    }
}
