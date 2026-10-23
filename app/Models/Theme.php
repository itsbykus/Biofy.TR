<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Availability row for a theme whose design is defined in code (App\Themes\ThemeRegistry). */
#[Fillable(['key', 'is_active', 'is_premium', 'sort_order'])]
class Theme extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_premium' => 'boolean'];
    }
}
