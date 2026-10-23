<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'package_id', 'starts_at', 'ends_at', 'revoked_at', 'source', 'note', 'assigned_by'])]
class UserPackageAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** Active right now: started, not ended, not revoked. Evaluated at read time, so no cron is required. */
    public function scopeCurrent(Builder $query): Builder
    {
        $now = now();

        return $query->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    public function isCurrent(): bool
    {
        return $this->revoked_at === null
            && $this->starts_at->lte(now())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
