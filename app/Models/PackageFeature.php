<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['package_id', 'feature_key', 'value'])]
class PackageFeature extends Model
{
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
