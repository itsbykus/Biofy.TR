<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['custom_domain_id', 'check', 'passed', 'details', 'created_at'])]
class DomainVerification extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['passed' => 'boolean'];
    }
}
