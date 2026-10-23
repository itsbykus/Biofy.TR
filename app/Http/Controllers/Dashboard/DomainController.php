<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CustomDomain;
use App\Models\Profile;
use App\Services\Domains\DomainService;
use App\Services\Entitlements;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainController extends Controller
{
    public function __construct(
        private readonly DomainService $domains,
        private readonly Entitlements $entitlements,
        private readonly Settings $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.domains.index', [
            'domains' => CustomDomain::query()->where('user_id', $user->id)->with(['profile:id,slug,display_name', 'verifications' => fn ($q) => $q->limit(6)])->latest()->get(),
            'profiles' => $user->profiles()->where('status', '!=', 'archived')->get(['id', 'slug', 'display_name']),
            'allowed' => $this->settings->bool('domains.enabled') && $this->entitlements->allows($user, Entitlements::CUSTOM_DOMAIN),
            'platformEnabled' => $this->settings->bool('domains.enabled'),
            'limit' => $this->entitlements->limit($user, Entitlements::MAX_DOMAINS),
            'cnameTarget' => (string) $this->settings->get('domains.cname_target'),
            'aRecordIp' => (string) $this->settings->get('domains.a_record_ip'),
            'requireHttps' => $this->settings->bool('domains.require_https'),
            'instructions' => $this->settings->text('domains.instructions'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hostname' => ['required', 'string', 'max:253'],
            'profile_id' => ['nullable', 'integer'],
        ]);

        $profile = $this->ownedProfile($request, $data['profile_id'] ?? null);
        $domain = $this->domains->add($request->user(), $data['hostname'], $profile);

        return redirect()->route('domains.index')->with('success', __('domains.added', ['host' => $domain->hostname]));
    }

    public function verify(Request $request, CustomDomain $domain): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);
        $domain = $this->domains->verify($domain);

        $message = match (true) {
            $domain->isVerified() && $domain->isSecure() => ['success', __('domains.verified_secure')],
            $domain->isVerified() => ['warning', __('domains.verified_no_https')],
            default => ['error', __('domains.not_verified_yet')],
        };

        return redirect()->route('domains.index')->with($message[0], $message[1]);
    }

    public function update(Request $request, CustomDomain $domain): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);
        $data = $request->validate(['profile_id' => ['nullable', 'integer']]);

        $this->domains->assignProfile($domain, $this->ownedProfile($request, $data['profile_id'] ?? null));

        return redirect()->route('domains.index')->with('success', __('domains.updated'));
    }

    public function destroy(Request $request, CustomDomain $domain): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);
        $this->domains->remove($domain);

        return redirect()->route('domains.index')->with('success', __('domains.removed'));
    }

    private function authorizeDomain(Request $request, CustomDomain $domain): void
    {
        abort_unless($domain->user_id === $request->user()->id, 404);
    }

    private function ownedProfile(Request $request, mixed $id): ?Profile
    {
        if (! $id) {
            return null;
        }

        $profile = Profile::query()->whereKey((int) $id)->where('user_id', $request->user()->id)->first();
        abort_if($profile === null, 404);

        return $profile;
    }
}
