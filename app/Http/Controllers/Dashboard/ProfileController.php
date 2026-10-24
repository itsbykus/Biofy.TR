<?php

namespace App\Http\Controllers\Dashboard;

use App\Blocks\BlockRegistry;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\RespondsToEditor;
use App\Models\MediaFile;
use App\Models\Profile;
use App\Services\Entitlements;
use App\Services\MediaService;
use App\Services\ProfileRenderer;
use App\Services\ProfileService;
use App\Services\QrCodeService;
use App\Themes\ThemeRegistry;
use App\Themes\ThemeSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use RespondsToEditor;

    public function __construct(
        private readonly ProfileService $profiles,
        private readonly Entitlements $entitlements,
        private readonly BlockRegistry $blocks,
        private readonly ThemeRegistry $themes,
        private readonly ProfileRenderer $renderer,
        private readonly MediaService $media,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $profiles = $user->profiles()->withCount('blocks')->orderByRaw("CASE WHEN status = 'archived' THEN 1 ELSE 0 END")->latest('updated_at')->get();
        $active = $profiles->where('status', '!=', Profile::STATUS_ARCHIVED)->count();

        return view('dashboard.pages.index', [
            'profiles' => $profiles,
            'limit' => $this->entitlements->limit($user, Entitlements::MAX_PAGES),
            'canCreate' => $this->entitlements->withinLimit($user, Entitlements::MAX_PAGES, $active),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $active = $user->profiles()->where('status', '!=', Profile::STATUS_ARCHIVED)->count();

        if (! $this->entitlements->withinLimit($user, Entitlements::MAX_PAGES, $active)) {
            return redirect()->route('pages.index')->with('warning', __('profiles.limit_reached'));
        }

        return view('dashboard.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['slug' => strtolower(trim((string) $request->input('slug')))]);
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'min:3', 'max:30'],
        ]);

        $profile = $this->profiles->create($request->user(), $data);

        return redirect()->route('pages.edit', $profile)->with('success', __('profiles.created'));
    }

    public function edit(Request $request, Profile $profile): View
    {
        $this->authorize('view', $profile);
        $profile->load(['blocks', 'socialLinks', 'user', 'versions' => fn ($q) => $q->latest('version_no')->limit(10)]);

        return view('dashboard.pages.edit', $this->editorData($profile) + [
            'tab' => in_array($request->query('tab'), ['content', 'profile', 'social', 'theme', 'seo', 'settings'], true) ? $request->query('tab') : 'content',
        ]);
    }

    /** Everything the editor views need (also used to re-render fragments after AJAX saves). */
    public function editorData(Profile $profile): array
    {
        $user = $profile->user;
        $theme = $this->themes->get($profile->theme_key);

        return [
            'profile' => $profile,
            'registry' => $this->blocks,
            'themes' => $this->themes->active(),
            'themeSettings' => ThemeSettings::resolve($theme, $profile->theme_settings),
            'themeFields' => ThemeSettings::fields(),
            'fonts' => ThemeRegistry::FONTS,
            'media' => MediaFile::query()->where('user_id', $user->id)->latest('id')->limit(60)->get(),
            'can' => [
                'advanced_blocks' => $this->entitlements->allows($user, Entitlements::ADVANCED_BLOCKS),
                'premium_themes' => $this->entitlements->allows($user, Entitlements::PREMIUM_THEMES),
                'advanced_customization' => $this->entitlements->allows($user, Entitlements::ADVANCED_CUSTOMIZATION),
                'scheduling' => $this->entitlements->allows($user, Entitlements::SCHEDULED_LINKS),
                'seo_advanced' => $this->entitlements->allows($user, Entitlements::SEO_ADVANCED),
                'tracking' => $this->entitlements->allows($user, Entitlements::TRACKING_PIXELS),
                'slug_redirects' => $this->entitlements->allows($user, Entitlements::SLUG_REDIRECTS),
            ],
            'limits' => [
                'links' => $this->entitlements->limit($user, Entitlements::MAX_LINKS),
                'blocks' => $this->entitlements->limit($user, Entitlements::MAX_BLOCKS),
                'social' => $this->entitlements->limit($user, Entitlements::MAX_SOCIAL_LINKS),
            ],
            'usage' => [
                'links' => $profile->blocks->whereIn('type', $this->blocks->linkTypes())->count(),
                'blocks' => $profile->blocks->count(),
                'social' => $profile->socialLinks->count(),
            ],
            'storage' => ['used' => $this->media->usedBytes($user), 'quota' => $this->media->quotaBytes($user)],
            'platforms' => array_keys(config('biofy.social_platforms')),
            'badges' => config('biofy.badges'),
        ];
    }

    public function updateDetails(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:120'],
            'bio_short' => ['nullable', 'string', 'max:300'],
            'bio_long' => ['nullable', 'string', 'max:3000'],
            'contact_email' => ['nullable', 'email:rfc', 'max:191'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{5,25}$/'],
            'location' => ['nullable', 'string', 'max:120'],
            'badge' => ['nullable', Rule::in(config('biofy.badges'))],
            'avatar_media_id' => ['nullable', 'integer'],
            'banner_media_id' => ['nullable', 'integer'],
            'language' => ['required', Rule::in(config('biofy.locales'))],
        ]);

        $this->profiles->updateDetails($profile, $data);

        return $this->editorResponse($request, $profile, __('editor.saved'), 'profile');
    }

    public function updateSeo(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $data = $request->validate([
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'seo_image_media_id' => ['nullable', 'integer'],
            'seo_noindex' => ['nullable', 'boolean'],
        ]);
        $data['seo_noindex'] = $request->boolean('seo_noindex');

        $this->profiles->updateDetails($profile, $data);

        return $this->editorResponse($request, $profile, __('editor.saved'), 'seo');
    }

    /** Measurement tags of the page (a setting, applied immediately; not part of the draft). */
    public function updateTracking(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        abort_unless($this->entitlements->allows($request->user(), Entitlements::TRACKING_PIXELS), 403);
        $request->validate(['tracking' => ['nullable', 'array'], 'tracking.*' => ['nullable', 'string', 'max:40']]);

        $ids = \App\Support\Tracking::normalize((array) $request->input('tracking', []));
        $profile->forceFill(['tracking' => $ids ?: null])->save();

        return $this->editorResponse($request, $profile, __('tracking.saved'), 'seo');
    }

    public function updateTheme(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $data = $request->validate([
            'theme_key' => ['required', 'string', 'max:50'],
            'settings' => ['nullable', 'array'],
            'reset' => ['nullable', 'boolean'],
        ]);

        $themeChanged = $data['theme_key'] !== $profile->theme_key;
        $settings = $request->boolean('reset') ? [] : ($data['settings'] ?? []);
        $modeBefore = ThemeSettings::resolve($this->themes->get($profile->theme_key), $profile->theme_settings)['mode'];
        $this->profiles->setTheme($profile, $data['theme_key'], $settings);
        $modeChanged = ThemeSettings::resolve($this->themes->get($profile->theme_key), $profile->theme_settings)['mode'] !== $modeBefore;

        // Re-render the panel when its defaults changed (new theme, reset, light/dark switch).
        $fragments = $themeChanged || $modeChanged || $request->boolean('reset') ? ['theme-panel' => 'dashboard.pages.partials.theme-panel'] : [];

        return $this->editorResponse($request, $profile, __('editor.theme_saved'), 'theme', $fragments);
    }

    public function updateSlug(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorize('update', $profile);
        $request->validate(['slug' => ['required', 'string', 'min:3', 'max:30']]);

        $this->profiles->changeSlug($profile, (string) $request->input('slug'), $request->boolean('keep_redirect'));

        return redirect()->route('pages.edit', ['profile' => $profile, 'tab' => 'settings'])->with('success', __('profiles.slug_changed'));
    }

    public function publish(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('publish', $profile);

        $this->profiles->publish($profile, $request->user());

        return $this->editorResponse($request, $profile, __('profiles.published'), 'content');
    }

    public function unpublish(Request $request, Profile $profile): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->unpublish($profile);

        return $this->editorResponse($request, $profile, __('profiles.unpublished'), 'content');
    }

    public function revert(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->revertToPublished($profile);

        return redirect()->route('pages.edit', $profile)->with('success', __('profiles.reverted'));
    }

    public function archive(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorize('update', $profile);
        $this->profiles->archive($profile);

        return redirect()->route('pages.index')->with('success', __('profiles.archived'));
    }

    public function restore(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorize('delete', $profile);
        $this->profiles->restoreFromArchive($profile);

        return redirect()->route('pages.edit', $profile)->with('success', __('profiles.restored'));
    }

    public function destroy(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorize('delete', $profile);
        $request->validate(['confirm_slug' => ['required', 'in:'.$profile->slug]], ['confirm_slug.in' => __('profiles.delete_confirm_mismatch')]);

        $this->profiles->delete($profile);

        return redirect()->route('pages.index')->with('success', __('profiles.deleted'));
    }

    /** Live preview of the *draft* (never counted in analytics, never indexed). */
    public function preview(Request $request, Profile $profile): Response
    {
        $this->authorize('view', $profile);

        $data = $this->renderer->prepare($profile, $this->renderer->draftSnapshot($profile), preview: true);
        $request->attributes->set('profile_page', true);

        return response()->view('profile.show', $data)->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }

    public function qr(Request $request, Profile $profile, string $format, QrCodeService $qr): Response
    {
        $this->authorize('view', $profile);
        $url = $profile->canonicalUrl();

        if ($format === 'png') {
            return response($qr->png($url), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="'.$profile->slug.'-qr.png"',
            ]);
        }

        return response($qr->svg($url, 512), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.$profile->slug.'-qr.svg"',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
        ]);
    }
}
