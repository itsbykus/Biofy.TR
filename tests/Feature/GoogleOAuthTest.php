<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(Settings::class);
        $settings->set('oauth.google.enabled', true);
        $settings->set('oauth.google.client_id', 'test-client-id.apps.googleusercontent.com');
        $settings->set('oauth.google.client_secret', 'test-secret');
        config(['services.google' => ['client_id' => 'x', 'client_secret' => 'y', 'redirect' => 'http://localhost/auth/google/callback']]);
    }

    private function fakeGoogle(string $id, string $email, bool $verified = true): void
    {
        $user = (new SocialiteUser)->setRaw(['sub' => $id, 'email' => $email, 'email_verified' => $verified])
            ->map(['id' => $id, 'name' => 'Google Person', 'email' => $email]);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_new_google_user_completes_signup_with_verified_email(): void
    {
        $this->fakeGoogle('g-123', 'new.person@gmail.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('auth.google.complete'));
        $this->get(route('auth.google.complete'))->assertOk()->assertSee('new.person@gmail.com');

        $this->post(route('auth.google.finish'), ['name' => 'New Person', 'username' => 'newperson', 'terms' => '1'])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'new.person@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'g-123']);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogle('g-999', 'unverified@gmail.com', false);

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHas('error', __('auth.google_unverified'));
        $this->assertDatabaseMissing('users', ['email' => 'unverified@gmail.com']);
    }

    public function test_existing_local_account_is_never_merged_automatically(): void
    {
        $victim = $this->makeUser(['email' => 'victim@gmail.com']);
        $this->fakeGoogle('attacker-google-id', 'victim@gmail.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHas('error', __('auth.google_email_exists'));

        $this->assertGuest();
        $this->assertSame(0, SocialAccount::query()->where('user_id', $victim->id)->count());
    }

    public function test_linked_google_account_signs_in_by_subject_id(): void
    {
        $user = $this->makeUser(['email' => 'linked@example.com']);
        SocialAccount::query()->create(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'g-linked', 'provider_email' => 'other@gmail.com']);
        $this->fakeGoogle('g-linked', 'other@gmail.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_is_unavailable_when_not_configured(): void
    {
        app(Settings::class)->set('oauth.google.enabled', false);

        $this->get(route('auth.google.redirect'))->assertNotFound();
        $this->get(route('auth.google.callback'))->assertNotFound();
    }
}
