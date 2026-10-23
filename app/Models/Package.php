<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key', 'name', 'description', 'marketing_features', 'price_monthly', 'price_yearly', 'currency',
    'is_public', 'is_default', 'is_archived', 'is_highlighted', 'is_contact_only', 'contact_message',
    'contact_url', 'custom_for_user_id', 'sort_order',
])]
class Package extends Model
{
    use HasTranslations;

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'marketing_features' => 'array',
            'contact_message' => 'array',
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'is_archived' => 'boolean',
            'is_highlighted' => 'boolean',
            'is_contact_only' => 'boolean',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PackageFeature::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UserPackageAssignment::class);
    }

    public function customFor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custom_for_user_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeOnPricingPage(Builder $query): Builder
    {
        return $query->available()->where('is_public', true)->whereNull('custom_for_user_id')->orderBy('sort_order');
    }

    public function featureValue(string $key): ?string
    {
        return $this->features->firstWhere('feature_key', $key)?->value;
    }
}
