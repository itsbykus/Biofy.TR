<?php

namespace App\Services\System;

use App\Models\AnalyticsEvent;
use App\Models\ApiRequestLog;
use App\Models\CustomDomain;
use App\Models\ProfileSlugRedirect;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Services\Domains\DomainService;
use App\Services\Settings;
use App\Services\UserAccountService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

/**
 * Periodic housekeeping. Every task processes a bounded batch so a single run is
 * safe inside a web request on shared hosting. Triggered by:
 *   - system cron:  php artisan schedule:run   (every minute) or php artisan biofy:maintenance
 *   - URL cron:     GET /cron/{token}           (hosts that only offer "wget a URL" crons)
 *   - fallback:     light tasks when a staff member opens the admin dashboard
 */
class MaintenanceRunner
{
    private const LOCK = 'biofy.maintenance.lock';

    public function __construct(
        private readonly Settings $settings,
        private readonly DomainService $domains,
        private readonly BackupService $backups,
        private readonly UserAccountService $accounts,
    ) {
    }

    /** @return array<string, int|string> what was done */
    public function run(bool $includeHeavy = true): array
    {
        $lock = Cache::lock(self::LOCK, 600);

        if (! $lock->get()) {
            return ['skipped' => 'already_running'];
        }

        $report = [];

        try {
            $batch = (int) config('biofy.maintenance.batch_size');

            $report['analytics_pruned'] = $this->safely(fn () => $this->pruneAnalytics($batch));
            $report['salts_pruned'] = $this->safely(fn () => DB::table('analytics_salts')->where('date', '<', now()->subDays(3)->toDateString())->delete());
            $report['security_events_pruned'] = $this->safely(fn () => $this->pruneOlderThan(SecurityEvent::class, $this->settings->int('privacy.security_log_retention_days'), $batch));
            $report['api_logs_pruned'] = $this->safely(fn () => $this->pruneOlderThan(ApiRequestLog::class, 90, $batch));
            $report['redirects_pruned'] = $this->safely(fn () => ProfileSlugRedirect::query()->whereNotNull('expires_at')->where('expires_at', '<', now())->delete());
            $report['tokens_pruned'] = $this->safely(fn () => PersonalAccessToken::query()->whereNotNull('expires_at')->where('expires_at', '<', now()->subDay())->delete());
            $report['accounts_deleted'] = $this->safely(fn () => $this->processDeletions());

            if ($includeHeavy) {
                $report['domains_checked'] = $this->safely(fn () => $this->recheckDomains(5));
                $report['backup'] = $this->safely(fn () => $this->scheduledBackup());
            }

            $this->settings->set('cron.last_run_at', now()->toIso8601String());
        } finally {
            $lock->release();
        }

        return $report;
    }

    /** Called from the admin dashboard; runs at most every N minutes and never the heavy tasks. */
    public function runIfDue(): void
    {
        $last = $this->settings->get('cron.last_run_at');
        $interval = (int) config('biofy.maintenance.web_trigger_interval_minutes');

        if ($last === null || \Illuminate\Support\Carbon::parse($last)->diffInMinutes(now()) >= $interval) {
            $this->run(includeHeavy: false);
        }
    }

    private function pruneAnalytics(int $batch): int
    {
        $days = max(30, $this->settings->int('analytics.retention_days'));
        $cutoff = now()->subDays($days);
        $deleted = 0;

        // Bounded: at most 10 batches per run.
        for ($i = 0; $i < 10; $i++) {
            $ids = AnalyticsEvent::query()->where('created_at', '<', $cutoff)->orderBy('id')->limit($batch)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += AnalyticsEvent::query()->whereIn('id', $ids)->delete();
        }

        // Daily summaries are tiny but follow the same retention policy.
        DB::table('analytics_daily_summaries')->where('date', '<', $cutoff->toDateString())->limit($batch * 10)->delete();

        return $deleted;
    }

    private function pruneOlderThan(string $model, int $days, int $batch): int
    {
        if ($days <= 0) {
            return 0;
        }

        $ids = $model::query()->where('created_at', '<', now()->subDays($days))->orderBy('id')->limit($batch * 5)->pluck('id');

        return $ids->isEmpty() ? 0 : $model::query()->whereIn('id', $ids)->delete();
    }

    private function processDeletions(): int
    {
        $grace = max(0, $this->settings->int('privacy.account_deletion_grace_days'));
        $count = 0;

        User::query()->whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', now()->subDays($grace))
            ->limit(20)->get()
            ->each(function (User $user) use (&$count) {
                if (! $user->isSuperAdmin()) {
                    $this->accounts->deletePermanently($user, null);
                    $count++;
                }
            });

        return $count;
    }

    private function recheckDomains(int $limit): int
    {
        $domains = CustomDomain::query()
            ->where('status', '!=', CustomDomain::STATUS_DISABLED)
            ->where(fn ($q) => $q->whereNull('last_checked_at')->orWhere('last_checked_at', '<', now()->subHours(12)))
            ->where(fn ($q) => $q->where('status', CustomDomain::STATUS_VERIFIED)->orWhere('created_at', '>', now()->subDays(7)))
            ->orderBy('last_checked_at')
            ->limit($limit)
            ->get();

        foreach ($domains as $domain) {
            $this->domains->verify($domain);
        }

        return $domains->count();
    }

    private function scheduledBackup(): string
    {
        $schedule = (string) $this->settings->get('backups.schedule');

        if (! in_array($schedule, ['daily', 'weekly'], true)) {
            return 'off';
        }

        $last = $this->settings->get('system.last_backup_at');
        $due = $last === null || \Illuminate\Support\Carbon::parse($last)->diffInHours(now()) >= ($schedule === 'daily' ? 23 : 167);

        if (! $due) {
            return 'not_due';
        }

        return $this->backups->create('scheduled')->status;
    }

    private function safely(callable $task): int|string
    {
        try {
            return $task();
        } catch (Throwable $e) {
            Log::error('Maintenance task failed: '.$e->getMessage());

            return 'error';
        }
    }
}
