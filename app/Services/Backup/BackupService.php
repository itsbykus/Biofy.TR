<?php

namespace App\Services\Backup;

use App\Exceptions\UserFacingException;
use App\Models\BackupRecord;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Database + media backups designed for shared hosting:
 *  - rows are streamed table by table into gzip'd JSON lines (no mysqldump/shell, low memory),
 *  - media files are added to the zip from disk (never loaded in RAM),
 *  - optional encryption with a key derived from APP_KEY, chunked: libsodium secretstream (format 1)
 *    when the sodium extension is loaded, otherwise OpenSSL AES-256-GCM (format 2; many shared
 *    hosts ship PHP without sodium). Both formats can always be read back,
 *  - archives live in storage/app/private/backups (outside the web root).
 *
 * Restores rebuild the current schema with migrations and re-insert rows with
 * parameterised queries (no SQL text from the archive is ever executed).
 */
class BackupService
{
    private const MAGIC = "BIOFYBK1";

    /** AES-256-GCM: per chunk [len:4][final:1][iv:12][tag:16][ciphertext]; the chunk index and final flag are authenticated. */
    private const MAGIC_GCM = "BIOFYBK2";

    private const CHUNK = 1048576;

    /** Volatile or self-referential tables that are never backed up. */
    private const SKIP_TABLES = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'backup_records'];

    public function __construct(private readonly Settings $settings)
    {
    }

    public function directory(): string
    {
        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir, 0750);

        if (! File::exists($dir.'/.htaccess')) {
            File::put($dir.'/.htaccess', "Require all denied\nDeny from all\n");
        }

        return $dir;
    }

    public function path(BackupRecord $record): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.basename($record->filename);
    }

    public function create(string $type = 'manual', ?User $actor = null): BackupRecord
    {
        $this->extendLimits();
        $encrypt = $this->settings->bool('backups.encrypt');
        $uuid = (string) Str::uuid();
        $stamp = now()->format('Ymd-His');
        $filename = "biofy-{$stamp}-{$type}-".substr($uuid, 0, 8).($encrypt ? '.biofy-backup' : '.zip');

        $record = BackupRecord::query()->create([
            'uuid' => $uuid,
            'filename' => $filename,
            'type' => $type,
            'status' => 'running',
            'encrypted' => $encrypt,
            'created_by' => $actor?->id,
            'started_at' => now(),
        ]);

        $work = $this->tempDir('backup-'.$uuid);

        try {
            $dbFile = $work.'/database.jsonl.gz';
            $tables = $this->dumpDatabase($dbFile);
            $mediaFiles = $this->settings->bool('backups.include_media') ? $this->mediaFiles() : [];

            $manifest = [
                'format' => 1,
                'app' => 'biofy',
                'app_version' => config('biofy.version'),
                'created_at' => now()->toIso8601String(),
                'driver' => DB::connection()->getDriverName(),
                'migrations' => DB::table('migrations')->orderBy('id')->pluck('migration')->all(),
                'tables' => $tables,
                'database_sha256' => hash_file('sha256', $dbFile),
                'media_count' => count($mediaFiles),
                'includes_secrets' => false,
                'note' => 'Encrypted settings (SMTP/OAuth secrets) can only be read with the same APP_KEY.',
            ];

            $zipPath = $work.'/archive.zip';
            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Cannot create zip archive.');
            }

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $zip->addFile($dbFile, 'database.jsonl.gz');

            foreach ($mediaFiles as $relative => $absolute) {
                $zip->addFile($absolute, 'media/'.$relative);
            }

            if (! $zip->close()) {
                throw new RuntimeException('Cannot finalise zip archive.');
            }

            $target = $this->directory().DIRECTORY_SEPARATOR.$filename;

            if ($encrypt) {
                $this->encryptFile($zipPath, $target);
            } else {
                File::move($zipPath, $target);
            }

            $record->forceFill([
                'status' => 'completed',
                'size' => filesize($target),
                'checksum' => hash_file('sha256', $target),
                'manifest' => array_diff_key($manifest, ['migrations' => true]),
                'completed_at' => now(),
            ])->save();

            $this->settings->set('system.last_backup_at', now()->toIso8601String());
            $this->applyRetention();
        } catch (Throwable $e) {
            Log::error('Backup failed: '.$e->getMessage());
            $record->forceFill(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 1000), 'completed_at' => now()])->save();
        } finally {
            File::deleteDirectory($work);
        }

        return $record->fresh();
    }

    /** @return array{ok: bool, errors: list<string>, manifest: ?array} */
    public function verify(BackupRecord $record): array
    {
        $errors = [];
        $path = $this->path($record);

        if ($record->status !== 'completed' || ! is_file($path)) {
            return ['ok' => false, 'errors' => [__('backups.file_missing')], 'manifest' => null];
        }

        if ($record->checksum && ! hash_equals($record->checksum, (string) hash_file('sha256', $path))) {
            $errors[] = __('backups.checksum_mismatch');
        }

        $work = $this->tempDir('verify-'.$record->uuid);
        $manifest = null;

        try {
            $manifest = $this->extract($record, $work);

            if (! hash_equals((string) ($manifest['database_sha256'] ?? ''), (string) hash_file('sha256', $work.'/database.jsonl.gz'))) {
                $errors[] = __('backups.database_checksum_mismatch');
            }

            $unknown = array_diff($manifest['migrations'] ?? [], $this->knownMigrations());

            if ($unknown !== []) {
                $errors[] = __('backups.newer_version', ['version' => $manifest['app_version'] ?? '?']);
            }
        } catch (Throwable $e) {
            $errors[] = $e instanceof UserFacingException ? $e->getMessage() : __('backups.unreadable');
        } finally {
            File::deleteDirectory($work);
        }

        return ['ok' => $errors === [], 'errors' => $errors, 'manifest' => $manifest];
    }

    /**
     * Replaces all data with the backup's content. A pre-restore backup is taken first.
     *
     * @return BackupRecord the safety backup taken before restoring
     */
    public function restore(BackupRecord $record, User $actor): BackupRecord
    {
        $this->extendLimits();
        $check = $this->verify($record);

        if (! $check['ok']) {
            throw new UserFacingException(implode(' ', $check['errors']));
        }

        $safety = $this->create('pre_restore', $actor);

        if ($safety->status !== 'completed') {
            throw new UserFacingException(__('backups.safety_failed'));
        }

        $work = $this->tempDir('restore-'.$record->uuid);

        try {
            $this->extract($record, $work);

            // Keep the backup catalogue across the restore (it is not part of backups).
            $catalogue = DB::table('backup_records')->get()->map(fn ($r) => (array) $r)->all();

            Artisan::call('migrate:fresh', ['--force' => true]);

            $this->loadDatabase($work.'/database.jsonl.gz');
            // A backup from before a plan feature existed must not take that feature away.
            if (\App\Support\FeatureCatalogUpgrades::apply()) {
                app(\App\Services\Entitlements::class)->flushAll();
            }

            foreach (array_chunk($catalogue, 100) as $chunk) {
                DB::table('backup_records')->insert($chunk);
            }

            if (is_dir($work.'/media')) {
                $this->restoreMedia($work.'/media');
            }

            Cache::flush();
            $this->settings->flush();
        } finally {
            File::deleteDirectory($work);
        }

        return $safety;
    }

    public function delete(BackupRecord $record): void
    {
        File::delete($this->path($record));
        $record->delete();
    }

    /** Register an uploaded archive (e.g. moving between servers). */
    public function import(string $uploadedPath, string $originalName, ?User $actor): BackupRecord
    {
        $encrypted = str_ends_with(strtolower($originalName), '.biofy-backup');

        if (! $encrypted && ! str_ends_with(strtolower($originalName), '.zip')) {
            throw new UserFacingException(__('backups.invalid_upload'));
        }

        $uuid = (string) Str::uuid();
        $filename = 'biofy-'.now()->format('Ymd-His').'-uploaded-'.substr($uuid, 0, 8).($encrypted ? '.biofy-backup' : '.zip');
        $target = $this->directory().DIRECTORY_SEPARATOR.$filename;
        File::copy($uploadedPath, $target);

        $record = BackupRecord::query()->create([
            'uuid' => $uuid, 'filename' => $filename, 'type' => 'uploaded', 'status' => 'completed',
            'encrypted' => $encrypted, 'size' => filesize($target), 'checksum' => hash_file('sha256', $target),
            'created_by' => $actor?->id, 'started_at' => now(), 'completed_at' => now(),
        ]);

        $check = $this->verify($record);

        if (! $check['ok']) {
            $this->delete($record);

            throw new UserFacingException(implode(' ', $check['errors']));
        }

        $record->update(['manifest' => array_diff_key($check['manifest'], ['migrations' => true])]);

        return $record;
    }

    public function applyRetention(): void
    {
        $keep = max(1, $this->settings->int('backups.keep_last'));

        BackupRecord::query()->whereIn('type', ['manual', 'scheduled'])->where('status', 'completed')
            ->orderByDesc('id')->skip($keep)->take(1000)->get()
            ->each(fn (BackupRecord $r) => $this->delete($r));

        // Safety copies older than 30 days are removed as well.
        BackupRecord::query()->whereIn('type', ['pre_restore', 'pre_update'])->where('created_at', '<', now()->subDays(30))->get()
            ->each(fn (BackupRecord $r) => $this->delete($r));

        BackupRecord::query()->where('status', 'failed')->where('created_at', '<', now()->subDays(7))->delete();
    }

    /** @return array<string, int> table => row count */
    private function dumpDatabase(string $file): array
    {
        $gz = gzopen($file, 'wb6');
        $counts = [];

        foreach ($this->tables() as $table) {
            $counts[$table] = 0;
            $query = DB::table($table);
            $columns = Schema::getColumnListing($table);
            $rows = in_array('id', $columns, true) ? $query->lazyById(500) : $query->orderBy($columns[0])->lazy(500);

            foreach ($rows as $row) {
                gzwrite($gz, json_encode(['t' => $table, 'r' => (array) $row], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
                $counts[$table]++;
            }
        }

        gzclose($gz);

        return $counts;
    }

    private function loadDatabase(string $file): void
    {
        $gz = gzopen($file, 'rb');
        $columns = [];
        $batch = [];
        $batchTable = null;

        Schema::disableForeignKeyConstraints();

        try {
            while (! gzeof($gz)) {
                $line = gzgets($gz);

                if ($line === false || trim($line) === '') {
                    continue;
                }

                $entry = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $table = (string) $entry['t'];

                if (in_array($table, self::SKIP_TABLES, true) || ! Schema::hasTable($table)) {
                    continue;
                }

                if (! isset($columns[$table])) {
                    // Rows written by data migrations during migrate:fresh are replaced by the backup's own.
                    if ($batch !== []) {
                        DB::table($batchTable)->insert($batch);
                        $batch = [];
                    }

                    DB::table($table)->delete();
                }

                $columns[$table] ??= array_flip(Schema::getColumnListing($table));

                if ($batchTable !== $table && $batch !== []) {
                    DB::table($batchTable)->insert($batch);
                    $batch = [];
                }

                $batchTable = $table;
                // Only columns that exist in the current schema are inserted.
                $batch[] = array_intersect_key((array) $entry['r'], $columns[$table]);

                if (count($batch) >= 200) {
                    DB::table($table)->insert($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                DB::table($batchTable)->insert($batch);
            }
        } finally {
            gzclose($gz);
            Schema::enableForeignKeyConstraints();
        }
    }

    /** @return list<string> */
    private function tables(): array
    {
        // Only this connection's database: without a schema Laravel lists every database the user can
        // see (on shared hosting often WordPress' as well), and those tables are not ours to back up.
        $tables = array_map(fn ($t) => is_array($t) ? $t['name'] : $t, Schema::getTableListing(Schema::getCurrentSchemaListing(), false));
        $prefix = DB::connection()->getTablePrefix();

        return array_values(array_filter(
            array_map(fn ($t) => $prefix !== '' && str_starts_with($t, $prefix) ? substr($t, strlen($prefix)) : $t, $tables),
            fn ($t) => ! in_array($t, self::SKIP_TABLES, true) && ! str_starts_with($t, 'sqlite_'),
        ));
    }

    /** @return array<string, string> relative => absolute */
    private function mediaFiles(): array
    {
        $root = public_path('media');
        $files = [];

        if (! is_dir($root)) {
            return [];
        }

        foreach (File::allFiles($root) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if ($relative !== '.htaccess') {
                $files[$relative] = $file->getPathname();
            }
        }

        return $files;
    }

    private function restoreMedia(string $source): void
    {
        $root = public_path('media');
        File::ensureDirectoryExists($root);

        foreach (File::allFiles($root) as $file) {
            if ($file->getFilename() !== '.htaccess') {
                File::delete($file->getPathname());
            }
        }

        foreach (File::allFiles($source) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            // Only image files with safe relative paths are restored into the public media folder.
            if (! preg_match('#^[a-z]+/\d{4}/\d{2}/[a-z0-9]+\.(webp|png|jpe?g|gif)$#', $relative)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($root.'/'.$relative));
            File::copy($file->getPathname(), $root.'/'.$relative);
        }
    }

    /** @return array manifest */
    private function extract(BackupRecord $record, string $work): array
    {
        $zipPath = $work.'/archive.zip';

        if ($record->encrypted) {
            $this->decryptFile($this->path($record), $zipPath);
        } else {
            File::copy($this->path($record), $zipPath);
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new UserFacingException(__('backups.unreadable'));
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            // Zip-slip protection: only the expected entries, no traversal.
            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) {
                $zip->close();

                throw new UserFacingException(__('backups.unreadable'));
            }

            if ($name === 'manifest.json' || $name === 'database.jsonl.gz' || str_starts_with($name, 'media/')) {
                if (str_ends_with($name, '/')) {
                    continue;
                }

                File::ensureDirectoryExists(dirname($work.'/'.$name));
                copy('zip://'.$zipPath.'#'.$name, $work.'/'.$name);
            }
        }

        $zip->close();
        File::delete($zipPath);

        $manifest = json_decode((string) @file_get_contents($work.'/manifest.json'), true);

        if (! is_array($manifest) || ($manifest['app'] ?? null) !== 'biofy' || ! is_file($work.'/database.jsonl.gz')) {
            throw new UserFacingException(__('backups.unreadable'));
        }

        return $manifest;
    }

    /** "sodium" or "openssl"; config biofy.backups.cipher forces one (tests). */
    public function cipher(): string
    {
        $forced = config('biofy.backups.cipher');

        if (in_array($forced, ['sodium', 'openssl'], true)) {
            return $forced;
        }

        // Only the native extension: the pure-PHP fallback (sodium_compat) is too slow for large archives.
        return extension_loaded('sodium') ? 'sodium' : 'openssl';
    }

    private function encryptFile(string $source, string $target): void
    {
        $this->cipher() === 'sodium' ? $this->encryptSodium($source, $target) : $this->encryptGcm($source, $target);
        File::delete($source);
    }

    private function encryptSodium(string $source, string $target): void
    {
        $in = fopen($source, 'rb');
        $out = fopen($target, 'wb');
        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($this->key());
        fwrite($out, self::MAGIC.$header);

        while (! feof($in)) {
            $chunk = (string) fread($in, self::CHUNK);
            $tag = feof($in) ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
            $cipher = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag);
            fwrite($out, pack('N', strlen($cipher)).$cipher);
        }

        fclose($in);
        fclose($out);
    }

    private function encryptGcm(string $source, string $target): void
    {
        $in = fopen($source, 'rb');
        $out = fopen($target, 'wb');
        $salt = random_bytes(16);
        $key = $this->gcmKey($salt);
        fwrite($out, self::MAGIC_GCM.$salt);
        $index = 0;

        do {
            $chunk = (string) fread($in, self::CHUNK);
            $final = feof($in);
            $iv = random_bytes(12);
            $tag = '';
            // The chunk number and the final flag are authenticated: reordering or cutting the file is detected.
            $cipher = openssl_encrypt($chunk, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, pack('NC', $index++, (int) $final), 16);

            if ($cipher === false) {
                throw new RuntimeException('OpenSSL encryption failed.');
            }

            fwrite($out, pack('NC', strlen($cipher), (int) $final).$iv.$tag.$cipher);
        } while (! $final);

        fclose($in);
        fclose($out);
    }

    private function decryptFile(string $source, string $target): void
    {
        $in = fopen($source, 'rb');
        $magic = fread($in, strlen(self::MAGIC));

        try {
            match ($magic) {
                self::MAGIC => $this->decryptSodium($in, $target),
                self::MAGIC_GCM => $this->decryptGcm($in, $target),
                default => throw new UserFacingException(__('backups.unreadable')),
            };
        } finally {
            fclose($in);
        }
    }

    /** @param resource $in positioned after the magic */
    private function decryptSodium($in, string $target): void
    {
        $header = fread($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $this->key());
        $out = fopen($target, 'wb');
        $finished = false;

        while (! feof($in)) {
            $lengthBytes = fread($in, 4);

            if ($lengthBytes === '' || $lengthBytes === false) {
                break;
            }

            $length = unpack('N', $lengthBytes)[1];
            $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, (string) fread($in, $length));

            if ($result === false) {
                fclose($out);

                throw new UserFacingException(__('backups.decrypt_failed'));
            }

            fwrite($out, $result[0]);

            if ($result[1] === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                $finished = true;
                break;
            }
        }

        fclose($out);

        if (! $finished) {
            throw new UserFacingException(__('backups.decrypt_failed'));
        }
    }

    /** @param resource $in positioned after the magic */
    private function decryptGcm($in, string $target): void
    {
        $key = $this->gcmKey((string) fread($in, 16));
        $out = fopen($target, 'wb');
        $index = 0;
        $finished = false;

        while (! $finished) {
            $head = fread($in, 5);

            if ($head === false || strlen($head) !== 5) {
                break; // truncated: no final chunk
            }

            ['length' => $length, 'final' => $final] = unpack('Nlength/Cfinal', $head);

            if ($length > self::CHUNK + 1024) {
                fclose($out);

                throw new UserFacingException(__('backups.decrypt_failed'));
            }

            $iv = (string) fread($in, 12);
            $tag = (string) fread($in, 16);
            $cipher = $length > 0 ? (string) fread($in, $length) : '';
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, pack('NC', $index++, $final));

            if ($plain === false) {
                fclose($out);

                throw new UserFacingException(__('backups.decrypt_failed'));
            }

            fwrite($out, $plain);
            $finished = $final === 1;
        }

        fclose($out);

        if (! $finished) {
            throw new UserFacingException(__('backups.decrypt_failed'));
        }
    }

    private function appKeyBytes(): string
    {
        $appKey = (string) config('app.key');

        return str_starts_with($appKey, 'base64:') ? (string) base64_decode(substr($appKey, 7)) : $appKey;
    }

    /** Format 1 key (libsodium; works through sodium_compat when the extension is missing). */
    private function key(): string
    {
        return sodium_crypto_generichash('biofy-backup-v1|'.$this->appKeyBytes(), '', SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);
    }

    /** Format 2 key: HKDF-SHA256 from APP_KEY with a per-file salt. */
    private function gcmKey(string $salt): string
    {
        return hash_hkdf('sha256', $this->appKeyBytes(), 32, 'biofy-backup-v2', $salt);
    }

    private function knownMigrations(): array
    {
        return array_map(fn ($f) => pathinfo($f, PATHINFO_FILENAME), glob(database_path('migrations/*.php')) ?: []);
    }

    private function tempDir(string $name): string
    {
        $dir = storage_path('app/private/tmp/'.$name);
        File::ensureDirectoryExists($dir, 0750);

        return $dir;
    }

    private function extendLimits(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ignore_user_abort(true);
    }
}
