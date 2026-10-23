<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['profile_id', 'version_no', 'snapshot', 'created_by', 'created_at'])]
class ProfileVersion extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
