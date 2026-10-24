<?php

namespace Tests\Feature;

use App\Models\ShortLink;
use App\Services\ShortLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortLinkTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    public function test_create_redirect_and_count_clicks(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('shortlinks.store'), ['target_url' => 'example.com/uzun/adres?utm=1', 'title' => 'Kampanya'])
            ->assertRedirect()->assertSessionHas('success');
        $this->actingAs($user)->post(route('shortlinks.store'), ['target_url' => 'https://example.com/b', 'code' => 'Yaz-Kampanyasi'])->assertSessionHas('success');

        $auto = ShortLink::query()->whereNotNull('title')->firstOrFail();
        $this->assertMatchesRegularExpression('/^[a-z2-9]{6}$/', $auto->code);
        $this->assertSame('https://example.com/uzun/adres?utm=1', $auto->target_url);
        $this->assertTrue(ShortLink::query()->where('code', 'yaz-kampanyasi')->exists(), 'custom codes are lowercase');

        $this->withHeader('User-Agent', self::BROWSER)->get('/s/'.$auto->code)
            ->assertRedirect('https://example.com/uzun/adres?utm=1')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->withHeader('User-Agent', self::BROWSER)->get('/s/YAZ-KAMPANYASI')->assertRedirect('https://example.com/b');
        $this->withHeader('User-Agent', 'facebookexternalhit/1.1')->get('/s/'.$auto->code)->assertRedirect();

        $this->assertSame(1, $auto->fresh()->clicks, 'link previews and bots are not counted');
        $this->assertNotNull($auto->fresh()->last_clicked_at);
        $this->get('/s/yok-boyle-bir-kod')->assertNotFound();

        $this->actingAs($user)->get(route('shortlinks.index'))->assertOk()->assertSee($auto->displayUrl())->assertSee('Kampanya');
    }

    public function test_rules_limits_and_ownership(): void
    {
        $user = $this->makeUser();

        foreach (['javascript:alert(1)', 'https://user:pw@example.com', 'ftp://example.com'] as $bad) {
            $this->actingAs($user)->post(route('shortlinks.store'), ['target_url' => $bad])->assertSessionHasErrors('target_url');
        }

        $this->actingAs($user)->post(route('shortlinks.store'), ['target_url' => rtrim(config('app.url'), '/').'/s/abc'])->assertSessionHasErrors('target_url');
        $this->actingAs($user)->post(route('shortlinks.store'), ['target_url' => 'https://example.com', 'code' => '-bad'])->assertSessionHasErrors('code');
        $this->actingAs($user)->post(route('shortlinks.store'), ['target_url' => 'https://example.com', 'code' => 'ayni'])->assertSessionHas('success');
        $this->actingAs($this->makeUser())->post(route('shortlinks.store'), ['target_url' => 'https://example.com', 'code' => 'AYNI'])->assertSessionHasErrors('code');

        // Basic: 10 links.
        for ($i = ShortLink::query()->where('user_id', $user->id)->count(); $i < 10; $i++) {
            app(ShortLinkService::class)->create($user, 'https://example.com/'.$i);
        }
        $this->actingAs($user)->post(route('shortlinks.store'), ['target_url' => 'https://example.com/11'])->assertSessionHas('error');
        $this->assertSame(10, ShortLink::query()->where('user_id', $user->id)->count());

        $pro = $this->makeUser([], 'pro');
        $this->actingAs($pro)->post(route('shortlinks.store'), ['target_url' => 'https://example.com/pro'])->assertSessionHas('success');

        // Someone else's link: invisible.
        $link = ShortLink::query()->where('user_id', $user->id)->firstOrFail();
        $this->actingAs($pro)->put(route('shortlinks.update', $link), ['target_url' => 'https://evil.example'])->assertNotFound();
        $this->actingAs($pro)->delete(route('shortlinks.destroy', $link))->assertNotFound();
        $this->actingAs($pro)->get(route('shortlinks.qr', [$link, 'png']))->assertNotFound();

        $this->actingAs($user)->put(route('shortlinks.update', $link), ['target_url' => 'https://example.com/yeni', 'title' => 'Yeni'])->assertSessionHas('success');
        $this->assertSame('https://example.com/yeni', $link->fresh()->target_url);
        $this->actingAs($user)->get(route('shortlinks.qr', [$link, 'png']))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($user)->get(route('shortlinks.qr', [$link, 'svg']))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');

        $export = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->get(route('account.export'))->assertOk();
        $this->assertCount(10, json_decode($export->streamedContent() ?: $export->getContent(), true)['short_links']);
    }

    public function test_staff_can_switch_off_a_link_and_suspended_owners_links_stop(): void
    {
        $owner = $this->makeUser();
        $link = app(ShortLinkService::class)->create($owner, 'https://phishing.example');
        $admin = $this->makeSuperAdmin();

        $this->actingAs($this->makeUser())->get(route('admin.shortlinks.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.shortlinks.index', ['q' => 'phishing']))->assertOk()->assertSee('/s/'.$link->code);
        $this->actingAs($admin)->post(route('admin.shortlinks.toggle', $link))->assertRedirect();
        $this->assertFalse($link->fresh()->is_active);
        auth()->logout();
        $this->get('/s/'.$link->code)->assertNotFound();

        $link->forceFill(['is_active' => true])->save();
        $owner->forceFill(['status' => 'suspended'])->save();
        $this->get('/s/'.$link->code)->assertNotFound();
    }

    public function test_qr_generator(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('qr.show'))->assertOk()->assertSee(__('qr.empty'));
        $this->actingAs($user)->get(route('qr.show', ['content' => 'https://biofy.tr', 'fg' => '#1D4ED8', 'bg' => '#FFFFFF']))
            ->assertOk()->assertSee('<svg', false)->assertSee('fill="#1d4ed8"', false);
        $this->actingAs($user)->get(route('qr.download', ['png', 'content' => 'https://biofy.tr']))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($user)->get(route('qr.show', ['content' => 'x', 'fg' => '#FFFFFF', 'bg' => '#FFFFFF']))->assertSessionHasErrors('fg');
        $this->actingAs($user)->get(route('qr.download', ['png']))->assertSessionHasErrors('content');
    }
}
