<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'is_encrypted', 'updated_by'])]
class SystemSetting extends Model
{
    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }
}
