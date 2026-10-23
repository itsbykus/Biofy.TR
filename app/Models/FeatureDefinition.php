<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'name', 'description', 'group', 'type', 'default_value', 'platform_enabled', 'platform_cap', 'sort_order'])]
class FeatureDefinition extends Model
{
    use HasTranslations;

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'platform_enabled' => 'boolean',
            'platform_cap' => 'integer',
        ];
    }

    public function isBoolean(): bool
    {
        return $this->type === self::TYPE_BOOLEAN;
    }
}
