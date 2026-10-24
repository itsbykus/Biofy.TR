<?php

use App\Models\Page;
use App\Support\LegalPagePatches;
use Illuminate\Database\Migrations\Migration;

/**
 * 1.1.0: adds consent-gated measurement tags, short links and QR codes to the published legal
 * and help pages. Only pages that still contain the expected 1.0.4 sentences are changed (see
 * LegalPagePatches); hand-edited pages are left as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Page::query()->whereIn('slug', ['cookies', 'privacy', 'kvkk', 'terms', 'help'])->get()->each(function (Page $page) {
            $body = (array) $page->body;
            $changed = false;

            foreach (['tr', 'en'] as $locale) {
                if (! is_string($body[$locale] ?? null)) {
                    continue;
                }

                $patched = LegalPagePatches::apply($page->slug, $locale, $body[$locale]);

                if ($patched !== null) {
                    $body[$locale] = $patched;
                    $changed = true;
                }
            }

            if ($changed) {
                $page->forceFill(['body' => $body])->save();
            }
        });
    }

    public function down(): void
    {
        // Text changes are not reverted.
    }
};
