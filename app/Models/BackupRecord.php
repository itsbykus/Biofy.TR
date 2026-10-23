<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'filename', 'type', 'status', 'encrypted', 'size', 'checksum', 'manifest', 'error', 'created_by', 'started_at', 'completed_at'])]
class BackupRecord extends Model
{
    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
            'manifest' => 'array',
            'size' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
