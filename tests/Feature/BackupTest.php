<?php

namespace Tests\Feature;

use App\Models\BackupRecord;
use App\Models\Profile;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Uses real migrations (not a wrapping transaction) because restore rebuilds the schema. */
class BackupTest extends TestCase
{
    use DatabaseMigrations;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/private/backups'));
        File::deleteDirectory(storage_path('app/private/tmp'));
        parent::tearDown();
        // DatabaseMigrations rolled the schema back: make later RefreshDatabase tests migrate again.
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
    }

    public function test_backup_is_created_verified_and_protected(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->makeProfile($this->makeUser(), ['slug' => 'backedup']);

        $this->actingAs($admin)->post(route('admin.backups.store'))->assertSessionHas('success');
        $record = BackupRecord::query()->firstOrFail();

        $this->assertSame('completed', $record->status);
        $this->assertTrue($record->encrypted);
        $this->assertFileExists(app(BackupService::class)->path($record));
        $this->assertStringStartsWith(storage_path(), app(BackupService::class)->path($record), 'backups live outside the web root');
        $this->assertGreaterThan(0, $record->manifest['tables']['users']);

        $this->actingAs($admin)->post(route('admin.backups.verify', $record))->assertSessionHas('success');

        // Tampering is detected.
        file_put_contents(app(BackupService::class)->path($record), 'x', FILE_APPEND);
        $this->assertFalse(app(BackupService::class)->verify($record)['ok']);

        // Download needs staff permission and re-authentication.
        $this->actingAs($this->makeUser())->get(route('admin.backups.download', $record))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.backups.download', $record))->assertRedirect(route('password.confirm'));
    }

    public function test_restore_brings_back_data_and_keeps_a_safety_copy(): void
    {
        app(Settings::class)->set('backups.include_media', false);
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser(['email' => 'keep@example.com']);
        $this->makeProfile($user, ['slug' => 'beforebackup']);

        $backup = app(BackupService::class)->create('manual', $admin);
        $this->assertSame('completed', $backup->status, (string) $backup->error);

        // Change data after the backup.
        $user->delete();
        $this->makeUser(['email' => 'after@example.com']);

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.backups.restore', $backup), ['confirm' => 'wrong'])->assertSessionHasErrors('confirm');

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.backups.restore', $backup), ['confirm' => 'GERİ YÜKLE'])->assertRedirect(route('login'));

        $this->assertTrue(User::query()->where('email', 'keep@example.com')->exists());
        $this->assertFalse(User::query()->where('email', 'after@example.com')->exists());
        $this->assertTrue(Profile::query()->where('slug', 'beforebackup')->exists());
        $this->assertTrue(BackupRecord::query()->where('type', 'pre_restore')->where('status', 'completed')->exists(), 'safety backup taken first');
        $this->assertTrue(BackupRecord::query()->whereKey($backup->id)->exists(), 'backup catalogue survives the restore');
    }
}
