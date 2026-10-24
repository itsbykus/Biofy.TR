<?php

namespace Tests\Unit;

use App\Models\Page;
use App\Support\LegalPagePatches;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** In-place update of published legal pages (operators' own texts stay untouched). */
class LegalPagePatchesTest extends TestCase
{
    use RefreshDatabase;

    // Seeded like the feature tests: whichever RefreshDatabase test runs first builds the shared schema.
    protected bool $seed = true;

    /** A page that contains exactly the sentences a patch expects, plus operator-specific text. */
    private function pageFor(string $slug, string $locale): string
    {
        $operations = (new \ReflectionMethod(LegalPagePatches::class, 'operations'))->invoke(null)[$slug][$locale];
        $date = $locale === 'tr' ? '*Son güncelleme: 24 Ekim 2026*' : '*Last updated: 24 October 2026*';

        return $date."\n\nOperator: ACME Ltd., hello@acme.example\n\n".implode("\n\nfiller\n\n", array_map(fn ($op) => $op[1], $operations))."\n";
    }

    public function test_every_page_is_patched_once_and_keeps_the_operators_details(): void
    {
        foreach (['cookies', 'privacy', 'kvkk', 'terms', 'help'] as $slug) {
            foreach (['tr', 'en'] as $locale) {
                $body = $this->pageFor($slug, $locale);
                $patched = LegalPagePatches::apply($slug, $locale, $body);

                $this->assertNotNull($patched, "{$slug}/{$locale}");
                $this->assertStringContainsString('ACME Ltd., hello@acme.example', $patched);
                $this->assertNull(LegalPagePatches::apply($slug, $locale, $patched), "{$slug}/{$locale} applied twice");
                $this->assertNotNull(LegalPagePatches::apply($slug, $locale, str_replace("\n", "\r\n", $body)), 'CRLF from the admin textarea');
            }
        }

        $cookies = LegalPagePatches::apply('cookies', 'tr', $this->pageFor('cookies', 'tr'));
        $this->assertStringStartsWith('*Son güncelleme: 25 Ekim 2026*', $cookies);
        $this->assertStringContainsString('biofy.consent.*', $cookies);
    }

    public function test_hand_edited_pages_are_left_alone(): void
    {
        $body = $this->pageFor('privacy', 'tr');
        $edited = str_replace('| Yönetim işlem kayıtları |', '| Admin kayıtları |', $body);

        $this->assertNull(LegalPagePatches::apply('privacy', 'tr', $edited));
        $this->assertNull(LegalPagePatches::apply('about', 'tr', $body));
    }

    public function test_the_migration_updates_matching_pages_only(): void
    {
        Page::query()->whereIn('slug', ['cookies', 'terms'])->delete();
        $matching = Page::query()->create(['slug' => 'cookies', 'title' => ['tr' => 'Çerez'], 'body' => ['tr' => $this->pageFor('cookies', 'tr'), 'en' => 'Custom English text'], 'is_published' => true, 'is_system' => true]);
        $custom = Page::query()->create(['slug' => 'terms', 'title' => ['tr' => 'Koşullar'], 'body' => ['tr' => 'Kendi metnimiz'], 'is_published' => true, 'is_system' => true]);

        (require database_path('migrations/2026_10_25_000200_update_legal_pages.php'))->up();

        $this->assertStringContainsString('Ölçüm ve reklam araçları', $matching->fresh()->body['tr']);
        $this->assertSame('Custom English text', $matching->fresh()->body['en']);
        $this->assertSame(['tr' => 'Kendi metnimiz'], $custom->fresh()->body);
    }
}
