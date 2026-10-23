<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\Settings;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function registration(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Mithat Kuş',
            'username' => 'mito',
            'email' => 'mito@example.com',
            'password' => 'GüçlüParola123',
            'password_confirmation' => 'GüçlüParola123',
            'terms' => '1',
        ];
    }

    public function test_user_can_register_and_gets_package_and_first_page(): void
    {
        Notification::fake();

        $this->post(route('register.store'), $this->registration())->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'mito@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('GüçlüParola123', $user->password));
        $this->assertSame('basic', $user->packageAssignments()->first()->package->key);
        $this->assertTrue(Profile::query()->where('slug', 'mito')->where('user_id', $user->id)->exists());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_registration_validation_and_reserved_usernames(): void
    {
        // Many attempts from one IP would otherwise hit the registration rate limit (tested separately).
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        foreach (['admin', 'login', 'api', 'pricing', 'install', 'settings'] as $reserved) {
            $this->post(route('register.store'), $this->registration(['username' => $reserved, 'email' => $reserved.'@example.com']))
                ->assertSessionHasErrors('username');
        }

        $this->post(route('register.store'), $this->registration(['username' => 'Bad Name!']))->assertSessionHasErrors('username');
        $this->post(route('register.store'), $this->registration(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post(route('register.store'), $this->registration(['terms' => null]))->assertSessionHasErrors('terms');

        $this->makeUser(['email' => 'taken@example.com', 'username' => 'takenname']);
        $this->post(route('register.store'), $this->registration(['email' => 'TAKEN@example.com']))->assertSessionHasErrors('email');
        $this->post(route('register.store'), $this->registration(['username' => 'TakenName', 'email' => 'new@example.com']))->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_registration_can_be_closed_by_admin(): void
    {
        app(Settings::class)->set('registration.enabled', false);

        $this->post(route('register.store'), $this->registration())->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'mito@example.com']);
    }

    public function test_login_regenerates_session_and_logs_security_event(): void
    {
        $user = $this->makeUser(['email' => 'login@example.com']);
        $this->get(route('login'));
        $before = session()->getId();

        $this->post(route('login.store'), ['email' => 'login@example.com', 'password' => 'Password123!'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId(), 'session id must change after login (fixation)');
        $this->assertDatabaseHas('security_events', ['user_id' => $user->id, 'type' => 'login']);
    }

    public function test_wrong_password_and_unknown_user_give_same_error(): void
    {
        $user = $this->makeUser(['email' => 'known@example.com']);

        $known = $this->post(route('login.store'), ['email' => 'known@example.com', 'password' => 'wrong-password']);
        $unknown = $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password']);

        $known->assertSessionHasErrors(['email' => __('auth.failed')]);
        $unknown->assertSessionHasErrors(['email' => __('auth.failed')]);
        $this->assertGuest();
        $this->assertDatabaseHas('security_events', ['user_id' => $user->id, 'type' => 'login_failed']);
    }

    public function test_login_is_rate_limited(): void
    {
        $this->makeUser(['email' => 'brute@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => 'brute@example.com', 'password' => 'nope'.$i]);
        }

        $this->post(route('login.store'), ['email' => 'brute@example.com', 'password' => 'Password123!'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_login_and_is_logged_out_immediately(): void
    {
        $this->makeUser(['email' => 'susp@example.com'])->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->post(route('login.store'), ['email' => 'susp@example.com', 'password' => 'Password123!'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $active = $this->makeUser();
        $this->actingAs($active)->get(route('dashboard'))->assertOk();
        $active->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->actingAs($active->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_two_factor_challenge_and_replay_protection(): void
    {
        $secret = Totp::generateSecret();
        $user = $this->makeUser(['email' => '2fa@example.com']);
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => [Hash::make('ABCDE-FGHIJ')]])->save();

        $this->post(route('login.store'), ['email' => '2fa@example.com', 'password' => 'Password123!'])->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        $this->post(route('two-factor.login.store'), ['code' => '000000'])->assertSessionHasErrors('code');
        $code = Totp::code($secret);
        $this->post(route('two-factor.login.store'), ['code' => $code])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // The same code cannot be replayed for another login.
        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => '2fa@example.com', 'password' => 'Password123!']);
        $this->post(route('two-factor.login.store'), ['code' => $code])->assertSessionHasErrors('code');

        // Recovery codes work exactly once.
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'abcde-fghij'])->assertRedirect(route('dashboard'));
        $this->assertCount(0, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_password_reset_flow_does_not_reveal_accounts(): void
    {
        Notification::fake();
        $user = $this->makeUser(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertSessionHas('status', __('passwords.sent_generic'));
        $this->post(route('password.email'), ['email' => 'reset@example.com'])->assertSessionHas('status', __('passwords.sent_generic'));

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = (fn () => $this->token)->call($n);

            return true;
        });

        $this->post(route('password.update'), ['token' => $token, 'email' => 'reset@example.com', 'password' => 'YeniParola456', 'password_confirmation' => 'YeniParola456'])
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('YeniParola456', $user->fresh()->password));
        $this->assertDatabaseHas('security_events', ['user_id' => $user->id, 'type' => 'password_reset']);
    }

    public function test_registration_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('register.store'), $this->registration(['username' => 'user'.$i.'x', 'email' => "u{$i}@example.com"]));
            $this->post(route('logout'));
        }

        $this->post(route('register.store'), $this->registration(['username' => 'onemore', 'email' => 'more@example.com']))->assertStatus(429);
    }

    public function test_host_header_poisoning_is_blocked(): void
    {
        Notification::fake();
        // Same as AppServiceProvider does in production for an https APP_URL.
        config(['app.url' => 'https://biofy.test']);
        URL::forceRootUrl('https://biofy.test');
        URL::forceScheme('https');
        $user = $this->makeUser(['email' => 'host@example.com']);

        // An unknown Host header never reaches the application.
        $this->post('http://evil.example.com/forgot-password', ['email' => 'host@example.com'])->assertNotFound();
        Notification::assertNothingSent();

        // Links in e-mails are built from APP_URL, not from the request.
        $this->post('http://localhost/forgot-password', ['email' => 'host@example.com']);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use ($user) {
            $mail = $n->toMail($user);

            return str_starts_with($mail->viewData['actionUrl'], 'https://biofy.test/');
        });
    }

    public function test_email_verification(): void
    {
        $user = $this->makeUser(['email_verified_at' => null]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);

        $other = $this->makeUser(['email_verified_at' => null]);
        $tampered = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($other)->get($tampered)->assertForbidden();
        $this->actingAs($other)->get(route('verification.verify', ['id' => $other->id, 'hash' => sha1($other->email)]))->assertForbidden();
    }

    public function test_logout_other_sessions_requires_confirmation(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('security.sessions.others'))->assertRedirect(route('password.confirm'));
        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'Password123!'])->assertRedirect();
        $this->actingAs($user)->post(route('security.sessions.others'))->assertRedirect();
        $this->assertTrue(SecurityEvent::query()->where('type', 'logout_other_sessions')->exists());
    }
}
