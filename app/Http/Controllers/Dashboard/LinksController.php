<?php

namespace App\Http\Controllers\Dashboard;

use App\Blocks\BlockRegistry;
use App\Http\Controllers\Controller;
use App\Models\ProfileBlock;
use App\Services\Analytics\AnalyticsReporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** All link-type blocks across the user's pages, with 30-day click counts. */
class LinksController extends Controller
{
    public function index(Request $request, BlockRegistry $registry, AnalyticsReporter $reporter): View
    {
        $profileIds = $request->user()->profiles()->pluck('id');
        [$start, $end] = $reporter->range(null, null, 30);

        $blocks = ProfileBlock::query()->with('profile:id,slug,display_name,status')
            ->whereIn('profile_id', $profileIds)
            ->whereIn('type', $registry->linkTypes())
            ->orderBy('profile_id')->orderBy('position')
            ->get();

        $clicks = DB::table('analytics_daily_summaries')
            ->whereIn('block_id', $blocks->pluck('id'))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('block_id')
            ->pluck(DB::raw('SUM(clicks)'), 'block_id');

        return view('dashboard.links.index', ['blocks' => $blocks, 'clicks' => $clicks, 'registry' => $registry]);
    }
}
