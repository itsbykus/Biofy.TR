<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\ProfileSlugRedirect;
use App\Models\User;
use App\Services\BlockService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_draft_profile_is_not_public_until_published(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, ['slug' => 'draftpage']);

        $this->get('/draftpage')->assertNotFound();

        $this->actingAs($user)->postJson(route('pages.publish', $profile))->assertOk()->assertJson(['status' => 'published']);
        $this->get('/draftpage')->assertOk()->assertSee($user->name);
        $this->get('/DraftPage')->assertOk();

        $this->actingAs($user)->postJson(route('pages.unpublish', $profile))->assertOk();
        $this->get('/draftpage')->assertNotFound();
    }

    public function test_suspended_or_deleting_accounts_and_suspended_pages_are_hidden(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, ['slug' => 'hiddenpage'], publish: true);
        $this->get('/hiddenpage')->assertOk();

        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->get('/hiddenpage')->assertNotFound();

        $user->forceFill(['status' => User::STATUS_ACTIVE, 'deletion_requested_at' => now()])->save();
        $this->get('/hiddenpage')->assertNotFound();

        $user->forceFill(['deletion_requested_at' => null])->save();
        $profile->forceFill(['status' => Profile::STATUS_SUSPENDED])->save();
        $this->get('/hiddenpage')->assertNotFound();

        $profile->forceFill(['status' => Profile::STATUS_PUBLISHED, 'seo_noindex' => true])->save();
        app(ProfileService::class)->publish($profile->fresh(), $user);
        $this->get('/hiddenpage')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/sitemap-profiles-1.xml')->assertDontSee('hiddenpage');
    }

    public function test_draft_changes_do_not_alter_the_published_version(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, ['slug' => 'stable']);
        $block = app(BlockService::class)->create($profile, 'link', ['data' => ['url' => 'https://first.example.com', 'title' => 'First link']]);
        app(ProfileService::class)->publish($profile->fresh(), $user);

        $this->actingAs($user)->putJson(route('blocks.update', $block), ['data' => ['url' => 'https://second.example.com', 'title' => 'Second link']])->assertOk();
        app(BlockService::class)->create($profile->fresh(), 'text', ['data' => ['text' => 'Draft only text']]);

        $this->get('/stable')->assertSee('First link')->assertDontSee('Second link')->assertDontSee('Draft only text');
        $this->actingAs($user)->get(route('pages.preview', $profile))->assertSee('Second link')->assertSee('Draft only text');

        $this->actingAs($user)->post(route('pages.revert', $profile))->assertRedirect();
        $this->assertSame('First link', $profile->fresh()->blocks->firstWhere('type', 'link')->data['title']);
        $this->assertFalse($profile->fresh()->blocks->contains('type', 'text'));
    }

    public function test_slug_validation_and_uniqueness(): void
    {
        $user = $this->makeUser([], 'pro');
        $this->makeProfile($this->makeUser(), ['slug' => 'taken']);

        $this->actingAs($user)->post(route('pages.store'), ['display_name' => 'X', 'slug' => 'admin'])->assertSessionHasErrors('slug');
        $this->actingAs($user)->post(route('pages.store'), ['display_name' => 'X', 'slug' => 'taken'])->assertSessionHasErrors('slug');
        $this->actingAs($user)->post(route('pages.store'), ['display_name' => 'X', 'slug' => 'TAKEN'])->assertSessionHasErrors('slug');
        $this->actingAs($user)->post(route('pages.store'), ['display_name' => 'X', 'slug' => 'bad slug'])->assertSessionHasErrors('slug');
        $this->actingAs($user)->post(route('pages.store'), ['display_name' => 'X', 'slug' => 'freshslug'])->assertRedirect();
        $this->assertTrue(Profile::query()->where('slug', 'freshslug')->exists());
    }

    public function test_page_limit_is_enforced(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);

        $this->actingAs($user)->post(route('pages.store'), ['display_name' => 'Second', 'slug' => 'secondpage'])->assertSessionHas('error', __('profiles.limit_reached'));
        $this->assertFalse(Profile::query()->where('slug', 'secondpage')->exists());
    }

    public function test_slug_change_keeps_redirect_when_entitled(): void
    {
        $user = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($user, ['slug' => 'oldname'], publish: true);

        $this->actingAs($user)->put(route('pages.slug', $profile), ['slug' => 'newname', 'keep_redirect' => '1'])->assertRedirect();

        $this->get('/oldname')->assertRedirect('/newname');
        $this->get('/newname')->assertOk();
        $this->assertTrue(ProfileSlugRedirect::query()->where('old_slug', 'oldname')->exists());

        $other = $this->makeUser([], 'pro');
        $this->actingAs($other)->post(route('pages.store'), ['display_name' => 'X', 'slug' => 'oldname'])->assertSessionHasErrors('slug');
    }

    public function test_basic_users_get_no_redirect_on_slug_change(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, ['slug' => 'before'], publish: true);

        $this->actingAs($user)->put(route('pages.slug', $profile), ['slug' => 'after', 'keep_redirect' => '1']);

        $this->get('/before')->assertNotFound();
    }

    public function test_users_cannot_touch_other_users_pages(): void
    {
        $owner = $this->makeUser();
        $profile = $this->makeProfile($owner);
        $block = app(BlockService::class)->create($profile, 'link', ['data' => ['url' => 'https://example.com', 'title' => 'Mine']]);
        $attacker = $this->makeUser();

        $this->actingAs($attacker)->get(route('pages.edit', $profile))->assertForbidden();
        $this->actingAs($attacker)->get(route('pages.preview', $profile))->assertForbidden();
        $this->actingAs($attacker)->putJson(route('pages.details', $profile), ['display_name' => 'Hacked', 'language' => 'tr'])->assertForbidden();
        $this->actingAs($attacker)->postJson(route('pages.publish', $profile))->assertForbidden();
        $this->actingAs($attacker)->putJson(route('blocks.update', $block), ['data' => ['url' => 'https://evil.com', 'title' => 'x']])->assertForbidden();
        $this->actingAs($attacker)->deleteJson(route('blocks.destroy', $block))->assertForbidden();
        $this->actingAs($attacker)->postJson(route('blocks.store', $profile), ['type' => 'link', 'data' => ['url' => 'https://evil.com', 'title' => 'x']])->assertForbidden();
        $this->actingAs($attacker)->withSession(['auth.password_confirmed_at' => time()])->delete(route('pages.destroy', $profile), ['confirm_slug' => $profile->slug])->assertForbidden();

        $this->assertSame('Mine', $block->fresh()->data['title']);
        $this->assertNotNull($profile->fresh());
    }

    public function test_delete_requires_reauthentication_and_typed_confirmation(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, ['slug' => 'deleteme']);

        $this->actingAs($user)->delete(route('pages.destroy', $profile), ['confirm_slug' => 'deleteme'])->assertRedirect(route('password.confirm'));
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->delete(route('pages.destroy', $profile), ['confirm_slug' => 'wrong'])->assertSessionHasErrors('confirm_slug');
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->delete(route('pages.destroy', $profile), ['confirm_slug' => 'deleteme'])->assertRedirect(route('pages.index'));
        $this->assertNull($profile->fresh());
    }
}
