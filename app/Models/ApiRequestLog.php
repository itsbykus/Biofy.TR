<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'token_id', 'method', 'path', 'status', 'duration_ms', 'created_at'])]
class ApiRequestLog extends Model
{
    public const UPDATED_AT = null;
}
