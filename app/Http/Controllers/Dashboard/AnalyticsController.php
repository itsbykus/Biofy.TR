<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\Analytics\AnalyticsReporter;
use App\Services\Entitlements;
use App\Services\SecurityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsReporter $reporter,
        private readonly Entitlements $entitlements,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        [$profiles, $selected, $ids] = $this->scope($request);
        [$start, $end] = $this->reporter->range($request->query('from'), $request->query('to'), $this->entitlements->limit($user, Entitlements::ANALYTICS_HISTORY_DAYS));
        $detailed = $this->entitlements->allows($user, Entitlements::ANALYTICS_DETAILED);
        $overview = $ids ? $this->reporter->overview($ids, $start, $end) : null;

        return view('dashboard.analytics.index', [
            'profiles' => $profiles,
            'selected' => $selected,
            'start' => $start,
            'end' => $end,
            'overview' => $overview,
            'topBlocks' => $ids ? $this->reporter->topBlocks($ids, $start, $end) : [],
            'detailed' => $detailed,
            'canExport' => $this->entitlements->allows($user, Entitlements::ANALYTICS_EXPORT),
            'historyDays' => $this->entitlements->limit($user, Entitlements::ANALYTICS_HISTORY_DAYS),
            'breakdowns' => $detailed && $ids ? [
                'referrer_host' => $this->reporter->breakdown($ids, $start, $end, 'referrer_host'),
                'device' => $this->reporter->breakdown($ids, $start, $end, 'device'),
                'browser' => $this->reporter->breakdown($ids, $start, $end, 'browser'),
                'country' => $this->reporter->breakdown($ids, $start, $end, 'country'),
            ] : [],
            'weekly' => $detailed && $overview ? $this->reporter->rollup($overview['series'], 'week') : [],
            'monthly' => $detailed && $overview ? $this->reporter->rollup($overview['series'], 'month') : [],
        ]);
    }

    public function export(Request $request, SecurityLogger $security): StreamedResponse
    {
        $user = $request->user();
        [, $selected, $ids] = $this->scope($request);
        [$start, $end] = $this->reporter->range($request->query('from'), $request->query('to'), $this->entitlements->limit($user, Entitlements::ANALYTICS_HISTORY_DAYS));
        $security->log($user, SecurityLogger::DATA_EXPORTED, ['type' => 'analytics_csv']);

        $name = 'biofy-analytics-'.($selected?->slug ?? 'all').'-'.$start->toDateString().'_'.$end->toDateString().'.csv';

        return response()->streamDownload(function () use ($ids, $start, $end) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so spreadsheet apps show Turkish characters correctly

            foreach ($this->reporter->csvRows($ids, $start, $end) as $row) {
                // Neutralise spreadsheet formula injection in user-controlled labels.
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, $row));
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    /** Only the signed-in user's own pages can ever be selected. */
    private function scope(Request $request): array
    {
        $profiles = $request->user()->profiles()->orderBy('display_name')->get(['id', 'slug', 'display_name']);
        $selected = null;

        if ($request->filled('profile')) {
            $selected = $profiles->firstWhere('id', (int) $request->query('profile'));
            abort_if($selected === null, 404);
        }

        $ids = $selected ? [$selected->id] : $profiles->pluck('id')->all();

        return [$profiles, $selected instanceof Profile ? $selected : null, $ids];
    }
}
