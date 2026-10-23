<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['profile_id', 'platform', 'url', 'position'])]
class ProfileSocialLink extends Model
{
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
