<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_guests_and_regular_users_cannot_reach_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $user = $this->makeUser();
        foreach (['admin.dashboard', 'admin.users.index', 'admin.settings.edit', 'admin.backups.index', 'admin.system.index'] as $route) {
            $this->actingAs($user)->get(route($route, $route === 'admin.settings.edit' ? 'general' : []))->assertForbidden();
        }

        $this->actingAs($user)->post(route('admin.users.suspend', $this->makeUser()), ['reason' => 'x'])->assertForbidden();
    }

    public function test_moderator_has_only_granted_permissions(): void
    {
        $moderator = $this->makeModerator();
        $target = $this->makeUser();

        $this->actingAs($moderator)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($moderator)->post(route('admin.users.suspend', $target), ['reason' => 'Spam'])->assertRedirect();
        $this->assertTrue($target->fresh()->isSuspended());

        $this->actingAs($moderator)->get(route('admin.settings.edit', 'general'))->assertForbidden();
        $this->actingAs($moderator)->get(route('admin.packages.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($moderator)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.users.roles', $target), ['roles' => [Role::SUPERADMIN]])->assertForbidden();
        $this->assertFalse($target->fresh()->isSuperAdmin());
    }

    public function test_superadmin_role_cannot_be_self_assigned_or_obtained_by_registration(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Sneaky', 'username' => 'sneaky', 'email' => 'sneaky@example.com',
            'password' => 'Password12345', 'password_confirmation' => 'Password12345', 'terms' => '1',
            'roles' => ['superadmin'], 'role' => 'superadmin', 'is_admin' => 1,
        ]);

        $user = User::query()->where('email', 'sneaky@example.com')->firstOrFail();
        $this->assertCount(0, $user->roles);
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_last_superadmin_cannot_be_removed(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.users.roles', $admin), ['roles' => []])->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_suspend_reactivate_and_audit_trail(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, ['slug' => 'auditme'], publish: true);

        $this->actingAs($admin)->post(route('admin.users.suspend', $user), ['reason' => 'Terms violation'])->assertRedirect();
        $this->get('/auditme')->assertNotFound();
        $this->actingAs($admin)->post(route('admin.users.reactivate', $user))->assertRedirect();
        $this->get('/auditme')->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended', 'subject_id' => $user->id, 'actor_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.reactivated', 'subject_id' => $user->id]);

        $this->actingAs($admin)->post(route('admin.profiles.status', $profile), ['status' => 'suspended', 'reason' => 'Phishing'])->assertRedirect();
        $this->get('/auditme')->assertNotFound();
    }

    public function test_brand_images_are_uploaded_with_the_settings_form(): void
    {
        $admin = $this->makeSuperAdmin();
        $session = ['auth.password_confirmed_at' => time()];
        $this->actingAs($admin)->withSession($session)->get(route('admin.settings.edit', 'brand'))->assertOk()
            ->assertSee(__('admin.settings.media_choose'))->assertSee('data-file-preview-input', false);

        $img = imagecreatetruecolor(200, 80);
        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();

        $this->actingAs($admin)->withSession($session)->put(route('admin.settings.update', 'brand'), [
            'site__logo_media_id' => \Illuminate\Http\UploadedFile::fake()->createWithContent('logo.png', $png),
            'site__primary_color' => '#FFCF00', 'site__secondary_color' => '#111111', 'site__accent_color' => '#2563EB',
            'profiles__default_theme' => app(Settings::class)->get('profiles.default_theme'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $logo = \App\Models\MediaFile::query()->find(app(Settings::class)->get('site.logo_media_id'));
        $this->assertNotNull($logo);
        $this->assertNull($logo->user_id, 'platform asset');
        $this->actingAs($admin)->withSession($session)->get(route('admin.settings.edit', 'brand'))->assertSee($logo->url())->assertSee(__('admin.settings.media_replace'));
    }

    public function test_secret_settings_are_encrypted_masked_and_never_logged(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->put(route('admin.settings.update', 'mail'), [
            'mail__mailer' => 'smtp', 'mail__host' => 'smtp.example.com', 'mail__port' => 587, 'mail__encryption' => 'tls',
            'mail__username' => 'mailer', 'mail__password' => 'Sup3rS3cret!', 'mail__from_address' => 'no-reply@example.com', 'mail__from_name' => 'Biofy',
        ])->assertSessionHasNoErrors();

        $raw = \App\Models\SystemSetting::query()->where('key', 'mail.password')->value('value');
        $this->assertNotSame('Sup3rS3cret!', $raw);
        $this->assertSame('Sup3rS3cret!', app(Settings::class)->get('mail.password'));

        $this->actingAs($admin)->get(route('admin.settings.edit', 'mail'))->assertOk()->assertDontSee('Sup3rS3cret!');
        $this->assertStringNotContainsString('Sup3rS3cret!', AuditLog::query()->get()->toJson());

        // Empty secret field keeps the stored value.
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->put(route('admin.settings.update', 'mail'), [
            'mail__mailer' => 'smtp', 'mail__host' => 'smtp.example.com', 'mail__port' => 587, 'mail__encryption' => 'tls', 'mail__password' => '',
        ]);
        $this->assertSame('Sup3rS3cret!', app(Settings::class)->get('mail.password'));
    }

    public function test_settings_changes_require_reauthentication(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin)->put(route('admin.settings.update', 'general'), ['site__name' => 'Hacked'])->assertRedirect(route('password.confirm'));
        $this->assertSame('Biofy.TR', app(Settings::class)->get('site.name'));
    }

    public function test_brand_and_content_changes_show_on_the_site(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->put(route('admin.settings.update', 'announcement'), [
            'announcement__enabled' => '1', 'announcement__text' => ['tr' => 'Yeni temalar yayında!', 'en' => 'New themes!'], 'announcement__url' => '/themes', 'announcement__style' => 'brand',
            'maintenance__enabled' => '0', 'maintenance__message' => ['tr' => 'x'], 'maintenance__keep_profiles_online' => '1',
        ])->assertSessionHasNoErrors();

        $this->withSession(['locale' => 'tr'])->get('/')->assertSee('Yeni temalar yayında!');
    }

    public function test_admin_can_delete_user_with_confirmation(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $this->makeProfile($user);

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('admin.users.destroy', $user), ['confirm' => $user->username])->assertRedirect(route('admin.users.index'));

        $this->assertNull($user->fresh());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted']);
    }
}
