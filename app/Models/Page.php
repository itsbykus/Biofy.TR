<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'title', 'body', 'meta_description', 'is_published', 'is_system'])]
class Page extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'meta_description' => 'array',
            'is_published' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
