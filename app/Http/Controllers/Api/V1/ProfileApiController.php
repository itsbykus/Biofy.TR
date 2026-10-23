<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Profile;
use App\Services\Entitlements;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileApiController extends ApiController
{
    public function __construct(private readonly ProfileService $profiles)
    {
    }

    public function me(Request $request, Entitlements $entitlements): JsonResponse
    {
        $user = $request->user();
        $package = $entitlements->currentPackage($user);
        $token = $user->currentAccessToken();
        $token = $token instanceof \Laravel\Sanctum\PersonalAccessToken ? $token : null;

        return $this->data([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'locale' => $user->locale,
            'package' => $package ? ['key' => $package->key, 'name' => $package->translate('name')] : null,
            'limits' => [
                'pages' => $entitlements->limit($user, Entitlements::MAX_PAGES),
                'links_per_page' => $entitlements->limit($user, Entitlements::MAX_LINKS),
                'api_rate_limit_per_minute' => $entitlements->limit($user, Entitlements::API_RATE_LIMIT),
            ],
            'token' => ['name' => $token?->name, 'abilities' => $token?->abilities],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $page = Profile::query()->where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('id')->paginate($this->perPage($request));

        return $this->paginated($page, [Presenters::class, 'profile']);
    }

    public function show(Request $request, int $profile): JsonResponse
    {
        return $this->data(Presenters::profile($this->ownedProfile($request, $profile)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'min:3', 'max:30'],
        ]);

        $profile = $this->profiles->create($request->user(), $data);

        return $this->data(Presenters::profile($profile->fresh()), 201);
    }

    public function update(Request $request, int $profile): JsonResponse
    {
        $profile = $this->ownedProfile($request, $profile);
        abort_if($profile->status === Profile::STATUS_SUSPENDED, 403);
        $this->profiles->assertRevision($profile, $request->input('revision'));

        $data = $request->validate([
            'display_name' => ['sometimes', 'required', 'string', 'max:80'],
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'bio_short' => ['sometimes', 'nullable', 'string', 'max:300'],
            'bio_long' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'contact_email' => ['sometimes', 'nullable', 'email:rfc', 'max:191'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{5,25}$/'],
            'location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'language' => ['sometimes', Rule::in(config('biofy.locales'))],
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'seo_description' => ['sometimes', 'nullable', 'string', 'max:300'],
        ]);

        $this->profiles->updateDetails($profile, $data);

        return $this->data(Presenters::profile($profile->fresh()));
    }

    public function publish(Request $request, int $profile): JsonResponse
    {
        $profile = $this->ownedProfile($request, $profile);
        abort_if($profile->status === Profile::STATUS_SUSPENDED, 403);
        $version = $this->profiles->publish($profile, $request->user());

        return $this->data(Presenters::profile($profile->fresh()) + ['version' => $version->version_no]);
    }

    public function unpublish(Request $request, int $profile): JsonResponse
    {
        $profile = $this->ownedProfile($request, $profile);
        $this->profiles->unpublish($profile);

        return $this->data(Presenters::profile($profile->fresh()));
    }
}
