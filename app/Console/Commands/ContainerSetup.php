<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Models\UserPackageAssignment;
use App\Services\AuditLogger;
use App\Services\Entitlements;
use App\Services\Settings;
use App\Services\SlugService;
use App\Services\System\UpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Container (Coolify/Docker) counterpart of the web installer and the panel's
 * update button. Runs on every container start, before the web server:
 *   - first start:  migrate + seed + superadmin from BIOFY_ADMIN_* variables, then lock,
 *   - later starts: pre-migration backup (when migrations are pending) + migrate + record version.
 * Configuration comes only from environment variables; no .env file is written.
 */
class ContainerSetup extends Command
{
    protected $signature = 'biofy:container-setup';

    protected $description = 'Install or update Biofy.TR inside a container (non-interactive, environment driven)';

    private const USERNAME_PATTERN = '/^(?=.{3,30}$)[a-z0-9](?:[a-z0-9]|[_-](?=[a-z0-9]))*$/';

    public function handle(UpdateService $updates, Settings $settings): int
    {
        if (! $this->environmentIsValid()) {
            return self::FAILURE;
        }

        if (! $this->waitForDatabase((int) (env('BIOFY_DB_WAIT_SECONDS') ?: 90))) {
            $this->error('Database is not reachable. Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD.');

            return self::FAILURE;
        }

        $lock = (string) config('biofy.install_lock');
        $installedVersion = Schema::hasTable('system_settings')
            ? DB::table('system_settings')->where('key', 'system.installed_version')->value('value')
            : null;

        if ($installedVersion === null) {
            return $this->install($settings, $lock);
        }

        if (! is_file($lock)) {
            // The database is installed but the storage volume is new (or was lost).
            $this->warn('Install marker missing although the database is installed; recreating it. If the storage volume was lost, restore uploads/backups from your own copy.');
            $this->writeLock($lock);
        }

        return $this->update($updates);
    }

    private function environmentIsValid(): bool
    {
        $ok = true;

        if (! str_starts_with((string) config('app.key'), 'base64:') || strlen(base64_decode(substr((string) config('app.key'), 7), true) ?: '') !== 32) {
            $this->error('APP_KEY is missing or invalid. Set it once and never change it (it encrypts stored secrets and backups).');
            $this->line('Example value you can use: base64:'.base64_encode(random_bytes(32)));
            $ok = false;
        }

        $url = (string) config('app.url');
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (! preg_match('#^https?://[^/]+#i', $url) || ($path !== '' && $path !== '/')) {
            $this->error('APP_URL must be the full site address without a sub-folder, e.g. https://biofy.example.com');
            $ok = false;
        }

        if (config('database.default') === 'sqlite') {
            $this->error('DB_CONNECTION must be mysql or mariadb in a container deployment.');
            $ok = false;
        }

        return $ok;
    }

    private function waitForDatabase(int $seconds): bool
    {
        $deadline = time() + max(1, $seconds);

        do {
            try {
                DB::connection()->getPdo();

                return true;
            } catch (Throwable $e) {
                $this->line('Waiting for the database... ('.class_basename($e).')');
                DB::purge();
                sleep(3);
            }
        } while (time() < $deadline);

        return false;
    }

    private function install(Settings $settings, string $lock): int
    {
        $admin = [
            'name' => trim((string) env('BIOFY_ADMIN_NAME', 'Site Admin')),
            'username' => strtolower(trim((string) env('BIOFY_ADMIN_USERNAME', ''))),
            'email' => strtolower(trim((string) env('BIOFY_ADMIN_EMAIL', ''))),
            'password' => (string) env('BIOFY_ADMIN_PASSWORD', ''),
        ];

        // Same rules as the web installer; an existing (foreign) schema is never touched.
        $errors = [];

        // A database holding only an interrupted earlier attempt (no users yet) may be resumed.
        $tables = Schema::getTableListing(DB::connection()->getDatabaseName(), false);
        $resumable = in_array('migrations', $tables, true) && (! in_array('users', $tables, true) || DB::table('users')->doesntExist());

        if ($tables !== [] && ! $resumable) {
            $errors[] = 'The database is not empty but contains no Biofy.TR installation. Use an empty database.';
        }
        if (in_array($admin['username'], config('biofy.reserved_slugs'), true)) {
            $errors[] = 'BIOFY_ADMIN_USERNAME "'.$admin['username'].'" is reserved; choose another one.';
        }
        if (! preg_match(self::USERNAME_PATTERN, $admin['username'])) {
            $errors[] = 'BIOFY_ADMIN_USERNAME is required (3-30 characters: a-z, 0-9, _ or -).';
        }
        if (! filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'BIOFY_ADMIN_EMAIL is required and must be a valid e-mail address.';
        }
        if (mb_strlen($admin['password']) < 10 || ! preg_match('/\pL/u', $admin['password']) || ! preg_match('/\d/', $admin['password'])) {
            $errors[] = 'BIOFY_ADMIN_PASSWORD is required: at least 10 characters with letters and digits.';
        }
        if ($admin['name'] === '') {
            $errors[] = 'BIOFY_ADMIN_NAME must not be empty.';
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('First start: installing Biofy.TR '.config('biofy.version').'...');

        Artisan::call('migrate', ['--force' => true, '--isolated' => true]);
        $this->line(trim(Artisan::output()));
        Cache::flush();

        if (app(SlugService::class)->isReserved($admin['username'])) {
            $this->error('BIOFY_ADMIN_USERNAME "'.$admin['username'].'" is reserved; choose another one.');

            return self::FAILURE;
        }

        Artisan::call('db:seed', ['--force' => true]);

        // config('app.name') is replaced by the site.name setting at boot, so read the variable itself.
        $siteName = mb_substr(trim((string) (env('APP_NAME') ?: 'Biofy.TR')), 0, 60);

        $settings->setMany([
            'site.name' => $siteName,
            'site.default_locale' => in_array(config('app.locale'), config('biofy.locales'), true) ? config('app.locale') : 'tr',
            'site.timezone' => in_array(env('BIOFY_TIMEZONE', 'Europe/Istanbul'), timezone_identifiers_list(), true) ? env('BIOFY_TIMEZONE', 'Europe/Istanbul') : 'Europe/Istanbul',
            'site.support_email' => $admin['email'],
            'mail.from_name' => $siteName,
            'system.installed_version' => config('biofy.version'),
            'system.installed_at' => now()->toIso8601String(),
        ]);

        $user = new User([
            'name' => $admin['name'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'password' => $admin['password'],
            'locale' => config('app.locale') === 'en' ? 'en' : 'tr',
        ]);
        $user->email_verified_at = now();
        $user->password_changed_at = now();
        $user->save();
        $user->roles()->attach(Role::query()->where('key', Role::SUPERADMIN)->value('id'), ['created_at' => now()]);

        if ($package = app(Entitlements::class)->defaultPackage()) {
            UserPackageAssignment::query()->create(['user_id' => $user->id, 'package_id' => $package->id, 'starts_at' => now(), 'source' => 'system']);
        }

        app(AuditLogger::class)->log('system.installed', $user, ['version' => config('biofy.version'), 'via' => 'container'], $user);
        $this->writeLock($lock);

        $this->info('Installed. Sign in at '.rtrim((string) config('app.url'), '/').'/login as '.$admin['email'].'. BIOFY_ADMIN_PASSWORD is no longer used and can be removed.');

        return self::SUCCESS;
    }

    private function update(UpdateService $updates): int
    {
        $pending = $updates->pendingMigrations();

        if ($pending === [] && ! $updates->isPending()) {
            $this->info('Biofy.TR '.$updates->codeVersion().' is up to date; no pending migrations.');

            return self::SUCCESS;
        }

        $backup = $pending !== [] && filter_var(env('BIOFY_BACKUP_BEFORE_MIGRATE', true), FILTER_VALIDATE_BOOL);
        $this->info(sprintf('Updating %s -> %s (%d pending migration(s)%s)...', $updates->installedVersion() ?? '?', $updates->codeVersion(), count($pending), $backup ? ', backup first' : ''));

        // One container at a time (rolling deployments share the storage volume and its lock files).
        $lock = Cache::lock('biofy:container-update', 900);

        try {
            $lock->block(600);
        } catch (Throwable) {
            $this->error('Another container is still updating the database; giving up.');

            return self::FAILURE;
        }

        try {
            // Re-check under the lock: another container may have finished the work meanwhile.
            if ($updates->pendingMigrations() === [] && ! $updates->isPending()) {
                $this->info('Already updated by another container.');

                return self::SUCCESS;
            }

            $result = $updates->run(null, $backup);
        } catch (Throwable $e) {
            $this->error('Update failed: '.$e->getMessage());
            $this->error('The database was not marked as updated. Fix the cause and redeploy; a pre-update backup (if taken) is listed in Admin -> Backups.');

            return self::FAILURE;
        } finally {
            $lock->release();
        }

        $this->line($result['output']);
        $this->info('Updated to '.$result['to'].($result['backup'] ? ' (backup: '.$result['backup']->filename.')' : '').'.');

        return self::SUCCESS;
    }

    private function writeLock(string $lock): void
    {
        if (! is_dir(dirname($lock))) {
            mkdir(dirname($lock), 0750, true);
        }

        file_put_contents($lock, json_encode(['installed_at' => gmdate('c'), 'version' => config('biofy.version'), 'via' => 'container']), LOCK_EX);
    }
}
