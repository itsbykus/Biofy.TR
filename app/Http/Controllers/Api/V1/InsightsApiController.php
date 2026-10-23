<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CustomDomain;
use App\Services\Analytics\AnalyticsReporter;
use App\Services\Entitlements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsightsApiController extends ApiController
{
    public function analytics(Request $request, int $profile, AnalyticsReporter $reporter, Entitlements $entitlements): JsonResponse
    {
        $profile = $this->ownedProfile($request, $profile);
        $user = $request->user();
        [$start, $end] = $reporter->range($request->query('from'), $request->query('to'), $entitlements->limit($user, Entitlements::ANALYTICS_HISTORY_DAYS));
        $overview = $reporter->overview([$profile->id], $start, $end);

        $data = [
            'profile_id' => $profile->id,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'totals' => $overview['totals'],
            'daily' => $overview['series'],
            'top_links' => array_map(fn ($b) => ['block_id' => $b['block_id'], 'label' => $b['label'], 'clicks' => $b['clicks']], $reporter->topBlocks([$profile->id], $start, $end)),
        ];

        // Detailed dimensions are a plan feature, enforced here as well as in the dashboard.
        if ($entitlements->allows($user, Entitlements::ANALYTICS_DETAILED)) {
            foreach (['referrer_host' => 'referrers', 'device' => 'devices', 'browser' => 'browsers', 'country' => 'countries'] as $dimension => $key) {
                $data[$key] = $reporter->breakdown([$profile->id], $start, $end, $dimension);
            }
        }

        return $this->data($data);
    }

    public function domains(Request $request): JsonResponse
    {
        $page = CustomDomain::query()->where('user_id', $request->user()->id)->orderBy('id')->paginate($this->perPage($request));

        return $this->paginated($page, [Presenters::class, 'domain']);
    }

    public function domain(Request $request, int $domain): JsonResponse
    {
        $domain = CustomDomain::query()->whereKey($domain)->where('user_id', $request->user()->id)->first();
        abort_if($domain === null, 404);

        return $this->data(Presenters::domain($domain));
    }
}
