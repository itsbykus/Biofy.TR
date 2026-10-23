<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Services\BlockService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function token($user, array $abilities = ['profiles:read', 'profiles:write', 'links:read', 'links:write', 'analytics:read', 'domains:read']): string
    {
        return $user->createToken('test', $abilities)->plainTextToken;
    }

    public function test_tokens_are_created_hashed_and_revocable_from_the_dashboard(): void
    {
        $user = $this->makeUser([], 'pro');

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('api-tokens.store'), ['name' => 'CI', 'abilities' => ['profiles:read']])->assertSessionHas('api.new_token');

        $plain = session('api.new_token');
        $stored = PersonalAccessToken::query()->firstOrFail();
        $this->assertNotSame(explode('|', $plain)[1], $stored->token, 'only the hash is stored');

        $this->withToken($plain)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.username', $user->username);

        $this->actingAs($user)->delete(route('api-tokens.destroy', $stored->id))->assertRedirect();
        $this->app['auth']->forgetGuards();
        $this->withToken($plain)->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_api_requires_plan_feature_and_valid_token(): void
    {
        $basic = $this->makeUser();
        $this->withToken($this->token($basic))->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('error.code', 'feature_unavailable');

        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson('/api/v1/me')->assertUnauthorized();
        $this->withToken('1|invalidtoken')->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_abilities_are_enforced(): void
    {
        $user = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($user);
        $readOnly = $this->token($user, ['profiles:read']);

        $this->withToken($readOnly)->getJson('/api/v1/profiles')->assertOk();
        $this->withToken($readOnly)->patchJson("/api/v1/profiles/{$profile->id}", ['display_name' => 'x'])->assertForbidden();
        $this->withToken($readOnly)->getJson("/api/v1/profiles/{$profile->id}/links")->assertForbidden();
    }

    public function test_profile_and_link_crud_with_tenant_isolation(): void
    {
        $user = $this->makeUser([], 'pro');
        $token = $this->token($user);

        $id = $this->withToken($token)->postJson('/api/v1/profiles', ['display_name' => 'Api Page', 'slug' => 'apipage'])
            ->assertCreated()->assertJsonPath('data.slug', 'apipage')->json('data.id');

        $this->withToken($token)->patchJson("/api/v1/profiles/{$id}", ['bio_short' => 'From API'])->assertOk()->assertJsonPath('data.bio_short', 'From API');

        $linkId = $this->withToken($token)->postJson("/api/v1/profiles/{$id}/links", ['data' => ['url' => 'https://api.example.com', 'title' => 'API link']])
            ->assertCreated()->json('data.id');
        $this->withToken($token)->postJson("/api/v1/profiles/{$id}/links", ['data' => ['url' => 'javascript:alert(1)', 'title' => 'x']])
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->withToken($token)->patchJson("/api/v1/links/{$linkId}", ['data' => ['title' => 'Renamed']])->assertOk()->assertJsonPath('data.data.title', 'Renamed');
        $this->withToken($token)->postJson("/api/v1/profiles/{$id}/publish")->assertOk()->assertJsonPath('data.status', 'published');
        $this->get('/apipage')->assertOk()->assertSee('Renamed');
        $this->withToken($token)->getJson("/api/v1/profiles/{$id}/analytics")->assertOk()->assertJsonStructure(['data' => ['totals', 'daily', 'top_links', 'referrers']]);

        // Another tenant gets 404 for every object of this user.
        $this->app['auth']->forgetGuards();
        $other = $this->makeUser([], 'pro');
        $otherToken = $this->token($other);
        $this->withToken($otherToken)->getJson("/api/v1/profiles/{$id}")->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $this->withToken($otherToken)->patchJson("/api/v1/profiles/{$id}", ['display_name' => 'pwned'])->assertNotFound();
        $this->withToken($otherToken)->patchJson("/api/v1/links/{$linkId}", ['data' => ['title' => 'pwned']])->assertNotFound();
        $this->withToken($otherToken)->deleteJson("/api/v1/links/{$linkId}")->assertNotFound();
        $this->withToken($otherToken)->getJson("/api/v1/profiles/{$id}/analytics")->assertNotFound();
        $this->withToken($otherToken)->getJson('/api/v1/profiles')->assertOk()->assertJsonCount(0, 'data');

        $this->assertSame('Api Page', Profile::query()->find($id)->display_name);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->deleteJson("/api/v1/links/{$linkId}")->assertNoContent();
    }

    public function test_rate_limit_per_token(): void
    {
        $user = $this->makeUser([], 'pro'); // 60/min
        app(\App\Services\UserAccountService::class)->setOverride($user, 'api.rate_limit', '3', null, null, $this->makeSuperAdmin());
        $token = $this->token($user->fresh());

        for ($i = 0; $i < 3; $i++) {
            $this->withToken($token)->getJson('/api/v1/me')->assertOk();
        }

        $this->withToken($token)->getJson('/api/v1/me')->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');
    }

    public function test_platform_switch_and_usage_logging(): void
    {
        $user = $this->makeUser([], 'pro');
        $token = $this->token($user);
        $this->withToken($token)->getJson('/api/v1/profiles')->assertOk();
        $this->assertDatabaseHas('api_request_logs', ['user_id' => $user->id, 'path' => '/api/v1/profiles', 'status' => 200]);

        app(Settings::class)->set('api.enabled', false);
        $this->withToken($token)->getJson('/api/v1/profiles')->assertStatus(503);
    }
}
