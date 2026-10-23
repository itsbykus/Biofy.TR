<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['profile_id', 'type', 'position', 'is_visible', 'data', 'starts_at', 'ends_at'])]
class ProfileBlock extends Model
{
    protected $attributes = ['is_visible' => true, 'position' => 0];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_visible' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
