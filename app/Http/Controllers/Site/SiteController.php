<?php

namespace App\Http\Controllers\Site;

use App\Blocks\BlockRegistry;
use App\Http\Controllers\Controller;
use App\Models\FeatureDefinition;
use App\Models\Package;
use App\Models\Page;
use App\Themes\ThemeRegistry;
use App\Themes\ThemeSettings;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(ThemeRegistry $themes): View
    {
        return view('site.home', [
            'themes' => collect($themes->active())->take(6)->map(fn ($t) => $t + ['preview' => ThemeSettings::resolve($t, [])])->all(),
            'packages' => $this->pricingPackages(),
        ]);
    }

    public function features(BlockRegistry $blocks): View
    {
        return view('site.features', ['blockGroups' => $blocks->grouped()]);
    }

    public function themes(ThemeRegistry $themes): View
    {
        return view('site.themes', [
            'themes' => collect($themes->active())->map(fn ($t) => $t + ['preview' => ThemeSettings::resolve($t, [])])->all(),
        ]);
    }

    public function pricing(): View
    {
        return view('site.pricing', [
            'packages' => $this->pricingPackages(),
            'features' => FeatureDefinition::query()->where('platform_enabled', true)->orderBy('group')->orderBy('sort_order')->get(),
        ]);
    }

    public function page(string $slug): View
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        // Markdown with raw HTML escaped and unsafe link schemes removed.
        $body = Str::markdown((string) $page->translate('body'), [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        return view('site.page', ['page' => $page, 'body' => $body]);
    }

    private function pricingPackages()
    {
        return Package::query()->onPricingPage()->with('features')->get();
    }
}
