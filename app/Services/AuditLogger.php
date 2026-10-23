<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Records administrative actions. Property values whose key looks like a secret
 * are replaced before anything touches the database.
 */
class AuditLogger
{
    private const SECRET_KEY_PATTERN = '/pass(word)?|secret|token|api[_-]?key|private|recovery|two_factor|cookie|session/i';

    public function log(string $action, ?Model $subject = null, array $properties = [], ?User $actor = null): AuditLog
    {
        $request = app()->runningInConsole() ? null : request();
        $actor ??= $request?->user();

        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : self::sanitize($properties),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            'created_at' => now(),
        ]);
    }

    public static function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(self::SECRET_KEY_PATTERN, $key)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = match (true) {
                is_array($value) => self::sanitize($value),
                is_string($value) => Str::limit($value, 500),
                default => $value,
            };
        }

        return $clean;
    }
}
