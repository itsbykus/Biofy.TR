<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['profile_id', 'type', 'block_id', 'item_index', 'visitor_hash', 'is_unique', 'referrer_host', 'device', 'browser', 'country', 'created_at'])]
class AnalyticsEvent extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_VIEW = 'view';

    public const TYPE_CLICK = 'click';

    protected function casts(): array
    {
        return ['is_unique' => 'boolean', 'created_at' => 'datetime'];
    }
}
