<?php

namespace Tests\Feature;

use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Re-authentication before sensitive actions ("Confirm your identity"). */
class ConfirmIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_password_fields_have_a_label_not_an_error_message(): void
    {
        $this->withSession(['locale' => 'tr'])->get(route('login'))->assertOk()
            ->assertSee('>Parola</label>', false)->assertDontSee('Parola hatalı');

        $this->actingAs($this->makeUser())->withSession(['locale' => 'tr'])->get(route('password.confirm'))->assertOk()
            ->assertSee('Parola<span', false)->assertDontSee('Parola hatalı', false);
    }

    public function test_the_waiting_form_is_sent_again_after_confirming(): void
    {
        $admin = $this->makeSuperAdmin();
        $form = ['blog__title' => ['tr' => 'Haberler', 'en' => 'News'], 'blog__description' => ['tr' => "Ürün\nnotları", 'en' => '']];

        // Used to come back to the settings page without saving anything.
        $this->actingAs($admin)->from(route('admin.settings.edit', 'blog'))
            ->put(route('admin.settings.update', 'blog'), $form)->assertRedirect(route('password.confirm'));
        $this->assertSame('Blog', app(Settings::class)->text('blog.title', 'tr'));

        $page = $this->post(route('password.confirm.store'), ['password' => 'Password123!'])->assertOk();
        $page->assertSee('action="'.route('admin.settings.update', 'blog').'"', false)
            ->assertSee('data-auto-submit', false)
            ->assertSee('<meta name="referrer" content="no-referrer">', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('name="blog__title[tr]" value="Haberler"', false)
            ->assertSee('name="blog__description[tr]" value="Ürün'."\n".'notları"', false);

        // What the page submits (by script, or the "Continue" button), without a Referer:
        // the user lands back on the settings page, not on the confirmation page.
        $this->put(route('admin.settings.update', 'blog'), $form)->assertRedirect(route('admin.settings.edit', 'blog'))->assertSessionHasNoErrors();
        $this->assertSame('Haberler', app(Settings::class)->text('blog.title', 'tr'));

        // Nothing is replayed twice.
        $this->post(route('password.confirm.store'), ['password' => 'Password123!'])->assertRedirect();
    }

    public function test_uploads_are_not_replayed_and_the_user_is_told_to_repeat(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->from(route('admin.backups.index'))
            ->post(route('admin.backups.upload'), ['backup' => UploadedFile::fake()->create('x.biofy-backup', 1)])
            ->assertRedirect(route('password.confirm'));

        $this->post(route('password.confirm.store'), ['password' => 'Password123!'])
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('status', __('auth.confirm_repeat'));
    }

    public function test_input_that_cannot_be_kept_is_not_lost_silently(): void
    {
        $this->actingAs($this->makeSuperAdmin())->from(route('admin.settings.edit', 'blog'))
            ->put(route('admin.settings.update', 'blog'), ['blog__title' => ['tr' => "Bozuk \xC3\x28 metin"]])
            ->assertRedirect(route('password.confirm'));

        $this->post(route('password.confirm.store'), ['password' => 'Password123!'])
            ->assertRedirect(route('admin.settings.edit', 'blog'))
            ->assertSessionHas('status', __('auth.confirm_repeat'));
    }

    public function test_confirmation_lifetime_is_a_setting(): void
    {
        $user = $this->makeUser();
        app(Settings::class)->set('security.confirm_timeout_minutes', 5);

        $fresh = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - 200])
            ->post(route('security.sessions.others'))->assertRedirect();
        $this->assertNotSame(route('password.confirm'), $fresh->headers->get('Location'));

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - 400])
            ->post(route('security.sessions.others'))->assertRedirect(route('password.confirm'));
    }
}
