<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureDefinition;
use App\Models\ReservedUsername;
use App\Models\Theme;
use App\Services\AuditLogger;
use App\Services\Entitlements;
use App\Services\SlugService;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Feature catalogue, theme availability and reserved words. */
class CatalogController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Entitlements $entitlements,
    ) {
    }

    public function features(): View
    {
        return view('admin.catalog.features', ['features' => FeatureDefinition::query()->orderBy('group')->orderBy('sort_order')->get()]);
    }

    public function updateFeature(Request $request, FeatureDefinition $feature): RedirectResponse
    {
        $data = $request->validate([
            'name.tr' => ['required', 'string', 'max:100'],
            'name.en' => ['nullable', 'string', 'max:100'],
            'description.tr' => ['nullable', 'string', 'max:300'],
            'description.en' => ['nullable', 'string', 'max:300'],
            'default_value' => ['required', 'string', 'max:20', 'regex:/^-?\d+$/'],
            'platform_cap' => ['nullable', 'integer', 'min:-1', 'max:100000000'],
        ]);

        $before = $feature->only(['default_value', 'platform_enabled', 'platform_cap']);
        $feature->update([
            'name' => array_filter($data['name']),
            'description' => array_filter($data['description'] ?? []),
            'default_value' => $feature->isBoolean() ? ((int) $data['default_value'] === 1 ? '1' : '0') : (string) max(-1, (int) $data['default_value']),
            'platform_enabled' => $request->boolean('platform_enabled'),
            'platform_cap' => $feature->isBoolean() ? null : ($data['platform_cap'] ?? null),
        ]);

        $this->entitlements->flushAll();
        $this->audit->log('feature.updated', $feature, ['key' => $feature->key, 'before' => $before, 'after' => $feature->only(['default_value', 'platform_enabled', 'platform_cap'])]);

        return back()->with('success', __('admin.saved'));
    }

    public function themes(ThemeRegistry $registry): View
    {
        $rows = Theme::query()->get()->keyBy('key');

        return view('admin.catalog.themes', [
            'themes' => collect($registry->definitions())->map(fn ($t, $key) => $registry->get($key) + ['row' => $rows->get($key)])->all(),
        ]);
    }

    public function updateTheme(Request $request, string $key, ThemeRegistry $registry): RedirectResponse
    {
        abort_unless($registry->has($key), 404);
        $data = $request->validate(['sort_order' => ['nullable', 'integer', 'min:0', 'max:999']]);

        if ($key === ThemeRegistry::FALLBACK && ! $request->boolean('is_active')) {
            return back()->with('error', __('admin.themes.fallback_required'));
        }

        Theme::query()->updateOrCreate(['key' => $key], [
            'is_active' => $request->boolean('is_active'),
            'is_premium' => $key === ThemeRegistry::FALLBACK ? false : $request->boolean('is_premium'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $registry->flush();
        $this->audit->log('theme.updated', null, ['key' => $key, 'active' => $request->boolean('is_active'), 'premium' => $request->boolean('is_premium')]);

        return back()->with('success', __('admin.saved'));
    }

    public function reserved(): View
    {
        return view('admin.catalog.reserved', [
            'words' => ReservedUsername::query()->orderBy('word')->get(),
            'system' => config('biofy.reserved_slugs'),
        ]);
    }

    public function storeReserved(Request $request, SlugService $slugs): RedirectResponse
    {
        $request->validate(['words' => ['required', 'string', 'max:5000']]);
        $added = [];

        foreach (preg_split('/[\s,]+/', strtolower((string) $request->input('words'))) as $word) {
            $word = trim($word);

            if ($word !== '' && strlen($word) <= 40 && preg_match('/^[a-z0-9._-]+$/', $word)) {
                ReservedUsername::query()->firstOrCreate(['word' => $word]);
                $added[] = $word;
            }
        }

        $slugs->flushReserved();
        $this->audit->log('reserved_words.added', null, ['words' => $added]);

        return back()->with('success', __('admin.reserved.added', ['count' => count($added)]));
    }

    public function destroyReserved(ReservedUsername $word, SlugService $slugs): RedirectResponse
    {
        $word->delete();
        $slugs->flushReserved();
        $this->audit->log('reserved_words.removed', null, ['word' => $word->word]);

        return back()->with('success', __('admin.deleted'));
    }
}
