<?php

namespace App\Http\Controllers;

use App\Blocks\BlockRegistry;
use App\Models\Profile;
use App\Models\ProfileSlugRedirect;
use App\Services\Analytics\AnalyticsRecorder;
use App\Services\ProfileRenderer;
use App\Support\VCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Public bio pages, tracked click redirects and vCards. */
class ProfilePageController extends Controller
{
    public function __construct(
        private readonly ProfileRenderer $renderer,
        private readonly AnalyticsRecorder $analytics,
        private readonly BlockRegistry $blocks,
    ) {
    }

    public function show(Request $request, string $slug): Response
    {
        $slug = strtolower($slug);
        $profile = $this->findVisible($slug);

        if ($profile === null) {
            $redirect = ProfileSlugRedirect::query()->where('old_slug', $slug)->with('profile.user')->first();

            if ($redirect && ($redirect->expires_at === null || $redirect->expires_at->isFuture()) && $redirect->profile?->isPubliclyVisible()) {
                return redirect('/'.$redirect->profile->slug, 301);
            }

            abort(404);
        }

        return $this->render($request, $profile, false);
    }

    /** Shared by the main host and customer domains. */
    public function render(Request $request, Profile $profile, bool $onCustomDomain): Response
    {
        $profile->loadMissing(['user', 'publishedVersion', 'customDomains']);
        $data = $this->renderer->prepare($profile, $profile->publishedVersion->snapshot, false, $onCustomDomain);

        $this->analytics->recordView($profile, $request);
        $request->attributes->set('profile_page', true);

        $response = response()->view('profile.show', $data);
        $response->headers->set('Cache-Control', 'no-cache, private');

        if ($data['seo']['noindex']) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    public function click(Request $request, int $profileId, int $blockId, ?int $item = null): RedirectResponse
    {
        $profile = Profile::query()->with(['user', 'publishedVersion'])->find($profileId);

        if (! $profile || ! $profile->isPubliclyVisible()) {
            abort(404);
        }

        return $this->clickFor($request, $profile, $blockId, $item);
    }

    public function clickFor(Request $request, Profile $profile, int $blockId, ?int $item): RedirectResponse
    {
        // Only blocks that are currently visible on the published page can be followed.
        $data = $this->renderer->prepare($profile, $profile->publishedVersion->snapshot);
        $block = collect($data['blocks'])->firstWhere('id', $blockId);
        $target = null;

        if ($block) {
            $target = $this->blocks->get($block['type'])->clickTarget($block['view'], $item);
        }

        if ($target === null) {
            return redirect('/'.$profile->slug);
        }

        $this->analytics->recordClick($profile, $blockId, $item, $request);

        // Destination was validated (http/https/mailto/tel only) when the block was saved.
        return redirect()->away($target, 302, ['Referrer-Policy' => 'origin', 'Cache-Control' => 'no-store']);
    }

    public function vcard(Request $request, string $slug): Response
    {
        $profile = $this->findVisible(strtolower($slug));
        abort_if($profile === null, 404);

        return $this->vcardFor($profile);
    }

    public function vcardFor(Profile $profile): Response
    {
        $data = $this->renderer->prepare($profile, $profile->publishedVersion->snapshot);
        abort_unless(collect($data['blocks'])->contains('type', 'contact'), 404);

        $p = $data['p'];
        $card = VCard::build([
            'name' => $p['display_name'],
            'title' => $p['title'],
            'email' => $p['contact_email'],
            'phone' => $p['contact_phone'],
            'url' => $profile->canonicalUrl(),
            'note' => $p['bio_short'],
        ]);

        return response($card, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$profile->slug.'.vcf"',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    private function findVisible(string $slug): ?Profile
    {
        if (! preg_match(config('biofy.slug.pattern'), $slug)) {
            return null;
        }

        $profile = Profile::query()->with(['user', 'publishedVersion', 'customDomains'])->where('slug', $slug)->first();

        return $profile && $profile->isPubliclyVisible() ? $profile : null;
    }
}
