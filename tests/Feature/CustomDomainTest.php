<?php

namespace Tests\Feature;

use App\Models\CustomDomain;
use App\Services\BlockService;
use App\Services\Domains\DnsResolver;
use App\Services\Domains\HttpsProbe;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomDomainTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private array $txt = [];

    protected function setUp(): void
    {
        parent::setUp();

        $test = $this;
        $this->app->instance(DnsResolver::class, new class($test) implements DnsResolver
        {
            public function __construct(private $test) {}

            public function txt(string $name): array { return $this->test->txtRecords($name); }

            public function cname(string $name): array { return []; }

            public function a(string $name): array { return []; }
        });

        $this->app->instance(HttpsProbe::class, new class extends HttpsProbe
        {
            public function check(string $hostname, string $expectedToken): array
            {
                return [true, 'ok'];
            }
        });
        $this->app->forgetInstance(\App\Services\Domains\DomainService::class);
    }

    public function txtRecords(string $name): array
    {
        return $this->txt[$name] ?? [];
    }

    public function test_basic_plan_cannot_add_domains(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post(route('domains.store'), ['hostname' => 'links.example.org'])->assertSessionHas('error', __('domains.requires_upgrade'));
        $this->assertSame(0, CustomDomain::query()->count());
    }

    public function test_full_verification_flow_and_serving(): void
    {
        $user = $this->makeUser([], 'pro');
        $profile = $this->makeProfile($user, ['slug' => 'domainpage']);
        $block = app(BlockService::class)->create($profile, 'link', ['data' => ['url' => 'https://dest.example.com', 'title' => 'Dest']]);
        app(ProfileService::class)->publish($profile->fresh(), $user);

        $this->actingAs($user)->post(route('domains.store'), ['hostname' => 'HTTPS://Links.Example.org/path', 'profile_id' => $profile->id])->assertSessionHasNoErrors();
        $domain = CustomDomain::query()->firstOrFail();
        $this->assertSame('links.example.org', $domain->hostname);
        // Built before requesting the customer host (the URL generator follows the last request's host).
        $verifyUrl = route('domains.verify', $domain);

        $this->actingAs($user)->post($verifyUrl)->assertSessionHas('error');
        $this->assertSame('pending', $domain->fresh()->status);

        // Not verified yet: the domain does not serve the profile...
        $this->get('https://links.example.org/')->assertNotFound();
        // ...but answers the ownership probe.
        $this->get('https://links.example.org/.well-known/biofy-domain-check')->assertOk()->assertSee($domain->verification_token);

        $this->txt['_biofy-verify.links.example.org'] = [$domain->txtRecordValue()];
        $this->actingAs($user)->post($verifyUrl)->assertSessionHas('success');
        $this->assertTrue($domain->fresh()->isVerified());
        $this->assertTrue($domain->fresh()->isSecure());

        $this->get('https://links.example.org/')->assertOk()->assertSee('Dest')->assertSee('<link rel="canonical" href="https://links.example.org/">', false)->assertCookieMissing('laravel_session');
        $this->get("https://links.example.org/go/{$profile->id}/{$block->id}")->assertRedirect('https://dest.example.com');

        // A customer domain never exposes the platform's own routes.
        $this->get('https://links.example.org/login')->assertNotFound();
        $this->get('https://links.example.org/admin')->assertNotFound();
        $this->get('https://links.example.org/dashboard')->assertNotFound();
        $this->post('https://links.example.org/login', ['email' => 'x'])->assertStatus(405);

        // Plain HTTP is upgraded.
        $this->get('http://links.example.org/')->assertRedirect('https://links.example.org/');
    }

    public function test_unknown_hosts_and_platform_hosts_are_rejected(): void
    {
        $user = $this->makeUser([], 'pro');
        $this->actingAs($user)->post(route('domains.store'), ['hostname' => 'localhost'])->assertSessionHasErrors('hostname');
        $this->actingAs($user)->post(route('domains.store'), ['hostname' => '192.168.1.1'])->assertSessionHasErrors('hostname');
        $this->actingAs($user)->post(route('domains.store'), ['hostname' => 'not a domain'])->assertSessionHasErrors('hostname');

        $this->get('http://unknown-host.example.net/')->assertNotFound()->assertSee(__('errors.unknown_host_title'));
        $this->get('http://unknown-host.example.net/login')->assertNotFound();
    }

    public function test_domain_can_belong_to_one_account_only(): void
    {
        $first = $this->makeUser([], 'pro');
        $second = $this->makeUser([], 'pro');

        $this->actingAs($first)->post(route('domains.store'), ['hostname' => 'shared.example.org'])->assertSessionHasNoErrors();
        $this->actingAs($second)->post(route('domains.store'), ['hostname' => 'shared.example.org'])->assertSessionHasErrors('hostname');

        $domain = CustomDomain::query()->firstOrFail();
        $this->actingAs($second)->post(route('domains.verify', $domain))->assertNotFound();
        $this->actingAs($second)->delete(route('domains.destroy', $domain))->assertNotFound();

        $secondProfile = $this->makeProfile($second);
        $this->actingAs($first)->put(route('domains.update', $domain), ['profile_id' => $secondProfile->id])->assertNotFound();
    }
}
