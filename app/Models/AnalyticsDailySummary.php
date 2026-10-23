<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['profile_id', 'block_id', 'date', 'views', 'unique_visitors', 'clicks'])]
class AnalyticsDailySummary extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
