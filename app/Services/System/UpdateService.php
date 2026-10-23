<?php

namespace App\Services\System;

use App\Exceptions\UserFacingException;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Backup\BackupService;
use App\Services\Settings;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Manual, verifiable update flow for FTP deployments (no remote code download):
 *   1. operator uploads the new release files by FTP (keeping .env and storage/),
 *   2. the panel detects code version > installed version and shows pending migrations,
 *   3. a pre-update backup is taken, migrations run, caches are cleared, version recorded.
 */
class UpdateService
{
    public function __construct(
        private readonly Settings $settings,
        private readonly BackupService $backups,
        private readonly AuditLogger $audit,
    ) {
    }

    public function codeVersion(): string
    {
        return (string) config('biofy.version');
    }

    public function installedVersion(): ?string
    {
        return $this->settings->get('system.installed_version');
    }

    public function isPending(): bool
    {
        $installed = $this->installedVersion();

        return $installed !== null && version_compare($this->codeVersion(), $installed, '>');
    }

    /** @return list<string> */
    public function pendingMigrations(): array
    {
        /** @var Migrator $migrator */
        $migrator = app('migrator');

        if (! $migrator->repositoryExists()) {
            return [];
        }

        $files = $migrator->getMigrationFiles([database_path('migrations')]);
        $ran = $migrator->getRepository()->getRan();

        return array_values(array_diff(array_keys($files), $ran));
    }

    /** $actor is null when the update is started by the container entrypoint. */
    public function run(?User $actor, bool $takeBackup = true): array
    {
        $from = $this->installedVersion();
        $backup = null;

        if ($takeBackup) {
            $backup = $this->backups->create('pre_update', $actor);

            if ($backup->status !== 'completed') {
                throw new UserFacingException(__('system.update_backup_failed'));
            }
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            Artisan::call('view:clear');
            Cache::flush();
            $this->settings->flush();
            $this->settings->set('system.installed_version', $this->codeVersion());
        } catch (Throwable $e) {
            $this->audit->log('system.update_failed', null, ['from' => $from, 'to' => $this->codeVersion(), 'error' => $e->getMessage()], $actor);

            throw new UserFacingException(__('system.update_failed', ['backup' => $backup?->filename ?? '-']));
        }

        $this->audit->log('system.updated', null, ['from' => $from, 'to' => $this->codeVersion(), 'backup' => $backup?->filename], $actor);

        return ['from' => $from, 'to' => $this->codeVersion(), 'output' => trim($output), 'backup' => $backup];
    }
}
