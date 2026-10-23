<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'locale', 'subject', 'body', 'is_customized'])]
class EmailTemplate extends Model
{
    protected function casts(): array
    {
        return ['is_customized' => 'boolean'];
    }
}
