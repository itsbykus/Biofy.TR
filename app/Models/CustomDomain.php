<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'profile_id', 'hostname', 'verification_token', 'status'])]
class CustomDomain extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_DISABLED = 'disabled';

    public const SSL_UNKNOWN = 'unknown';

    public const SSL_ACTIVE = 'active';

    public const SSL_FAILED = 'failed';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'ssl_status' => self::SSL_UNKNOWN,
        'check_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'dns_verified_at' => 'datetime',
            'ssl_checked_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(DomainVerification::class)->latest('id');
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isSecure(): bool
    {
        return $this->ssl_status === self::SSL_ACTIVE;
    }

    public function txtRecordName(): string
    {
        return '_biofy-verify.'.$this->hostname;
    }

    public function txtRecordValue(): string
    {
        return 'biofy-verify='.$this->verification_token;
    }
}
