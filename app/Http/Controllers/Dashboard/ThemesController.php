<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Entitlements;
use App\Themes\ThemeRegistry;
use App\Themes\ThemeSettings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemesController extends Controller
{
    public function index(Request $request, ThemeRegistry $themes, Entitlements $entitlements): View
    {
        $user = $request->user();

        return view('dashboard.themes.index', [
            'themes' => collect($themes->active())->map(fn ($t) => $t + ['preview' => ThemeSettings::resolve($t, [])])->all(),
            'profiles' => $user->profiles()->where('status', '!=', 'archived')->get(['id', 'slug', 'display_name', 'theme_key', 'draft_revision']),
            'canPremium' => $entitlements->allows($user, Entitlements::PREMIUM_THEMES),
        ]);
    }
}
