<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CustomDomain;
use App\Models\Profile;
use App\Models\ProfileBlock;

/** Public JSON shapes of the v1 API (kept stable; internal columns are never exposed). */
final class Presenters
{
    public static function profile(Profile $p): array
    {
        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'url' => $p->publicUrl(),
            'status' => $p->status,
            'display_name' => $p->display_name,
            'title' => $p->title,
            'bio_short' => $p->bio_short,
            'bio_long' => $p->bio_long,
            'contact_email' => $p->contact_email,
            'contact_phone' => $p->contact_phone,
            'location' => $p->location,
            'language' => $p->language,
            'theme' => $p->theme_key,
            'seo' => ['title' => $p->seo_title, 'description' => $p->seo_description, 'noindex' => $p->seo_noindex],
            'has_unpublished_changes' => $p->has_unpublished_changes,
            'revision' => $p->draft_revision,
            'published_at' => $p->published_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
            'updated_at' => $p->updated_at?->toIso8601String(),
        ];
    }

    public static function block(ProfileBlock $b): array
    {
        return [
            'id' => $b->id,
            'profile_id' => $b->profile_id,
            'type' => $b->type,
            'position' => $b->position,
            'visible' => $b->is_visible,
            'data' => $b->data,
            'starts_at' => $b->starts_at?->toIso8601String(),
            'ends_at' => $b->ends_at?->toIso8601String(),
            'updated_at' => $b->updated_at?->toIso8601String(),
        ];
    }

    public static function domain(CustomDomain $d): array
    {
        return [
            'id' => $d->id,
            'hostname' => $d->hostname,
            'profile_id' => $d->profile_id,
            'status' => $d->status,
            'ssl_status' => $d->ssl_status,
            'dns' => ['txt_name' => $d->txtRecordName(), 'txt_value' => $d->txtRecordValue()],
            'dns_verified_at' => $d->dns_verified_at?->toIso8601String(),
            'last_checked_at' => $d->last_checked_at?->toIso8601String(),
            'last_error' => $d->last_error,
        ];
    }
}
