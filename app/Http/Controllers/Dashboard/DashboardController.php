<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsReporter;
use App\Services\Entitlements;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AnalyticsReporter $reporter,
        private readonly Entitlements $entitlements,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $profiles = $user->profiles()->withCount('blocks')->where('status', '!=', 'archived')->latest('updated_at')->get();
        [$start, $end] = $this->reporter->range(null, null, 30);
        $overview = $profiles->isEmpty() ? null : $this->reporter->overview($profiles->pluck('id')->all(), $start, $end);

        return view('dashboard.index', [
            'profiles' => $profiles,
            'overview' => $overview,
            'package' => $this->entitlements->currentPackage($user),
            'start' => $start,
            'end' => $end,
        ]);
    }
}
