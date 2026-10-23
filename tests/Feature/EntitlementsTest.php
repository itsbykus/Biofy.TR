<?php

namespace Tests\Feature;

use App\Models\FeatureDefinition;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackageAssignment;
use App\Services\Entitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function e(): Entitlements
    {
        return app(Entitlements::class);
    }

    public function test_exactly_three_starting_packages_exist(): void
    {
        $this->assertSame(['basic', 'pro', 'premium'], Package::query()->orderBy('sort_order')->pluck('key')->all());
        $premium = Package::query()->where('key', 'premium')->first();
        $this->assertTrue($premium->is_contact_only);
        $this->assertSame('Daha fazlasına mı ihtiyacınız var? Bizimle irtibata geçiniz.', $premium->translate('contact_message', 'tr'));
    }

    public function test_resolution_order_package_override_platform(): void
    {
        $user = $this->makeUser();
        $this->assertSame(15, $this->e()->limit($user, Entitlements::MAX_LINKS));
        $this->assertFalse($this->e()->allows($user, Entitlements::ANALYTICS_DETAILED));

        $this->assignPackage($user, 'pro');
        $user->refresh();
        $this->assertSame(100, $this->e()->limit($user, Entitlements::MAX_LINKS));
        $this->assertTrue($this->e()->allows($user, Entitlements::ANALYTICS_DETAILED));

        // User-specific override wins over the package...
        $admin = $this->makeSuperAdmin();
        app(\App\Services\UserAccountService::class)->setOverride($user, Entitlements::MAX_LINKS, '250', null, null, $admin);
        $user->refresh();
        $this->assertSame(250, $this->e()->limit($user, Entitlements::MAX_LINKS));

        // ...but never over a platform cap.
        FeatureDefinition::query()->where('key', Entitlements::MAX_LINKS)->update(['platform_cap' => 200]);
        $this->e()->flushAll();
        $this->assertSame(200, $this->e()->limit($user, Entitlements::MAX_LINKS));

        // A feature disabled platform-wide is off for everybody, overrides included.
        app(\App\Services\UserAccountService::class)->setOverride($user, Entitlements::API_ACCESS, '1', null, null, $admin);
        FeatureDefinition::query()->where('key', Entitlements::API_ACCESS)->update(['platform_enabled' => false]);
        $this->e()->flushAll();
        $this->assertFalse($this->e()->allows($user->fresh(), Entitlements::API_ACCESS));
    }

    public function test_suspended_account_has_no_entitlements(): void
    {
        $user = $this->makeUser([], 'pro');
        $this->assertTrue($this->e()->allows($user, Entitlements::ADVANCED_BLOCKS));

        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->assertFalse($this->e()->allows($user, Entitlements::ADVANCED_BLOCKS));
        $this->assertSame(0, $this->e()->limit($user, Entitlements::MAX_LINKS));
    }

    public function test_admin_changes_apply_immediately_without_stale_cache(): void
    {
        $user = $this->makeUser();
        $this->assertFalse($this->e()->allows($user, Entitlements::REMOVE_BRANDING));

        $admin = $this->makeSuperAdmin();
        $basic = Package::query()->where('key', 'basic')->firstOrFail();
        $features = $basic->features()->pluck('value', 'feature_key')->all();
        $features[Entitlements::REMOVE_BRANDING] = '1';

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.packages.update', $basic), [
                'key' => 'basic', 'name' => ['tr' => 'Basic'], 'currency' => 'TRY', 'is_public' => '1', 'is_default' => '1',
                'features' => $features, 'features_present' => '1',
            ])->assertSessionHasNoErrors();

        // A fresh service instance (next request) sees the change.
        app()->forgetInstance(Entitlements::class);
        $this->assertTrue(app(Entitlements::class)->allows($user->fresh(), Entitlements::REMOVE_BRANDING));
        $this->assertDatabaseHas('audit_logs', ['action' => 'package.updated', 'subject_id' => $basic->id]);
    }

    public function test_expired_assignment_and_override_fall_back_without_cron(): void
    {
        $user = $this->makeUser();
        UserPackageAssignment::query()->create([
            'user_id' => $user->id, 'package_id' => Package::query()->where('key', 'pro')->value('id'),
            'starts_at' => now()->subDay(), 'ends_at' => now()->addHour(), 'source' => 'admin',
        ]);
        $this->e()->flushUser($user);
        $this->assertSame('pro', $this->e()->currentPackage($user)->key);

        $this->travel(2)->hours();
        $this->assertSame('basic', $this->e()->currentPackage($user)->key);

        $admin = $this->makeSuperAdmin();
        app(\App\Services\UserAccountService::class)->setOverride($user, Entitlements::ANALYTICS_EXPORT, '1', null, now()->addMinutes(30), $admin);
        app()->forgetInstance(Entitlements::class);
        $this->assertTrue(app(Entitlements::class)->allows($user->fresh(), Entitlements::ANALYTICS_EXPORT));

        $this->travel(1)->hours();
        app()->forgetInstance(Entitlements::class);
        $this->assertFalse(app(Entitlements::class)->allows($user->fresh(), Entitlements::ANALYTICS_EXPORT));
    }

    public function test_admin_can_assign_package_and_history_is_kept(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $pro = Package::query()->where('key', 'pro')->first();

        $this->actingAs($admin)->post(route('admin.users.package', $user), ['package_id' => $pro->id, 'note' => 'Trial'])->assertSessionHasNoErrors();
        $this->assertSame('pro', $this->e()->currentPackage($user->fresh())->key);

        $basic = Package::query()->where('key', 'basic')->first();
        $this->actingAs($admin)->post(route('admin.users.package', $user), ['package_id' => $basic->id])->assertSessionHasNoErrors();
        $this->assertSame(2, $user->packageAssignments()->where('source', 'admin')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.package_assigned', 'subject_id' => $user->id]);
    }

    public function test_feature_gate_blocks_backend_routes_not_only_ui(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);

        $this->actingAs($user)->get(route('analytics.export'))->assertRedirect(route('plan.show'));
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('api-tokens.store'), ['name' => 'x', 'abilities' => ['profiles:read']])->assertRedirect(route('plan.show'));
        $this->assertSame(0, $user->tokens()->count());
    }
}
