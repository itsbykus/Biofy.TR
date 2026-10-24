<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'slug', 'display_name', 'title', 'bio_short', 'bio_long', 'avatar_media_id', 'banner_media_id',
    'contact_email', 'contact_phone', 'location', 'badge', 'theme_key', 'theme_settings', 'seo_title',
    'seo_description', 'seo_image_media_id', 'seo_noindex', 'language', 'tracking',
])]
class Profile extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ARCHIVED = 'archived';

    /** Mirrors the column defaults (the editor's revision counter must start at 1 in memory too). */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'draft_revision' => 1,
        'has_unpublished_changes' => true,
        'seo_noindex' => false,
        'theme_key' => 'minimal-light',
        'language' => 'tr',
    ];

    protected function casts(): array
    {
        return [
            'theme_settings' => 'array',
            'seo_noindex' => 'boolean',
            'tracking' => 'array',
            'has_unpublished_changes' => 'boolean',
            'published_at' => 'datetime',
            'verified_at' => 'datetime',
            'draft_revision' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ProfileBlock::class)->orderBy('position')->orderBy('id');
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(ProfileSocialLink::class)->orderBy('position')->orderBy('id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProfileVersion::class);
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(ProfileVersion::class, 'published_version_id');
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'avatar_media_id');
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'banner_media_id');
    }

    public function seoImage(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'seo_image_media_id');
    }

    public function customDomains(): HasMany
    {
        return $this->hasMany(CustomDomain::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_version_id')
            ->whereHas('user', fn (Builder $q) => $q->where('status', User::STATUS_ACTIVE)->whereNull('deletion_requested_at'));
    }

    /** Single source of truth for "may a visitor see this profile right now". */
    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_version_id !== null
            && $this->user !== null
            && $this->user->isActive();
    }

    public function markDirty(): void
    {
        $this->forceFill([
            'draft_revision' => $this->draft_revision + 1,
            'has_unpublished_changes' => true,
        ])->save();
    }

    /** The verified custom domain that should be used as canonical URL, if any. */
    public function canonicalDomain(): ?CustomDomain
    {
        return $this->customDomains
            ->first(fn (CustomDomain $d) => $d->isVerified() && $d->isSecure());
    }

    public function publicUrl(): string
    {
        return url('/'.$this->slug);
    }

    public function canonicalUrl(): string
    {
        $domain = $this->canonicalDomain();

        return $domain ? 'https://'.$domain->hostname.'/' : $this->publicUrl();
    }
}
