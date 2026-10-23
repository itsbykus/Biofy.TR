<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\Profile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Everything a verified customer domain may serve. Invoked directly by the
 * HandleCustomDomains middleware (no session, no auth, no admin routes).
 */
class CustomDomainController extends Controller
{
    public function __construct(private readonly ProfilePageController $pages)
    {
    }

    public function handle(Request $request, array $entry): Response
    {
        app()->setLocale(SetLocale::fromHeaders($request));

        $profile = Profile::query()->with(['user', 'publishedVersion', 'customDomains'])->find($entry['profile_id']);

        if (! $profile || ! $profile->isPubliclyVisible()) {
            return response(view('errors.domain-pending'), 404);
        }

        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return response('', 405, ['Allow' => 'GET, HEAD']);
        }

        $path = trim($request->path(), '/');

        if ($path === '') {
            return $this->pages->render($request, $profile, true);
        }

        if (preg_match('#^go/(\d+)/(\d+)(?:/(\d+))?$#', $path, $m) && (int) $m[1] === $profile->id) {
            return $this->pages->clickFor($request, $profile, (int) $m[2], isset($m[3]) ? (int) $m[3] : null);
        }

        if ($path === 'vcard') {
            return $this->pages->vcardFor($profile);
        }

        if ($path === 'robots.txt') {
            $indexable = ! ($profile->publishedVersion->snapshot['profile']['seo_noindex'] ?? false);

            return response("User-agent: *\n".($indexable ? "Allow: /\n" : "Disallow: /\n"), 200, ['Content-Type' => 'text/plain']);
        }

        return response(view('errors.404'), 404);
    }
}
