<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'target_url', 'title'])]
class ShortLink extends Model
{
    protected $attributes = ['clicks' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return [
            'clicks' => 'integer',
            'is_active' => 'boolean',
            'last_clicked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Public address on the main site (never on a customer's domain). */
    public function shortUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/s/'.$this->code;
    }

    /** Address without the scheme, for display. */
    public function displayUrl(): string
    {
        return preg_replace('#^https?://#', '', $this->shortUrl());
    }
}
