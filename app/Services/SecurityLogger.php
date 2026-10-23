<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Support\Str;

/** Account-level security trail shown to the user (Security page) and to staff. */
class SecurityLogger
{
    public const LOGIN = 'login';

    public const LOGIN_FAILED = 'login_failed';

    public const LOGOUT = 'logout';

    public const LOGOUT_OTHER_SESSIONS = 'logout_other_sessions';

    public const PASSWORD_CHANGED = 'password_changed';

    public const PASSWORD_RESET = 'password_reset';

    public const EMAIL_CHANGED = 'email_changed';

    public const EMAIL_VERIFIED = 'email_verified';

    public const TWO_FACTOR_ENABLED = 'two_factor_enabled';

    public const TWO_FACTOR_DISABLED = 'two_factor_disabled';

    public const TWO_FACTOR_FAILED = 'two_factor_failed';

    public const RECOVERY_CODE_USED = 'recovery_code_used';

    public const GOOGLE_LINKED = 'google_linked';

    public const GOOGLE_UNLINKED = 'google_unlinked';

    public const API_TOKEN_CREATED = 'api_token_created';

    public const API_TOKEN_REVOKED = 'api_token_revoked';

    public const ACCOUNT_SUSPENDED = 'account_suspended';

    public const ACCOUNT_REACTIVATED = 'account_reactivated';

    public const DELETION_REQUESTED = 'deletion_requested';

    public const DELETION_CANCELLED = 'deletion_cancelled';

    public const DATA_EXPORTED = 'data_exported';

    public const LOCKOUT = 'lockout';

    public function log(?User $user, string $type, array $metadata = []): SecurityEvent
    {
        $request = app()->runningInConsole() ? null : request();

        return SecurityEvent::query()->create([
            'user_id' => $user?->getKey(),
            'type' => $type,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            'metadata' => $metadata === [] ? null : AuditLogger::sanitize($metadata),
            'created_at' => now(),
        ]);
    }
}
