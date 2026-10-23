<?php

namespace App\Services\Analytics;

use App\Blocks\BlockRegistry;
use App\Models\AnalyticsDailySummary;
use App\Models\AnalyticsEvent;
use App\Models\ProfileBlock;
use App\Services\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read side of analytics. Totals and time series come from the pre-aggregated
 * daily summary table; only the "detailed" breakdowns touch raw events, and
 * always within a bounded date range.
 */
class AnalyticsReporter
{
    public function __construct(
        private readonly Settings $settings,
        private readonly BlockRegistry $blocks,
    ) {
    }

    /**
     * Clamp a requested range to what the plan allows.
     *
     * @return array{0: Carbon, 1: Carbon} local dates (site time zone), inclusive
     */
    public function range(?string $from, ?string $to, int $historyDays): array
    {
        $tz = $this->timezone();
        $today = now($tz)->startOfDay();

        $end = $this->parseDate($to, $tz) ?? $today;
        $start = $this->parseDate($from, $tz) ?? $end->copy()->subDays(29);

        if ($end->gt($today)) {
            $end = $today;
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($historyDays > 0) {
            $earliest = $today->copy()->subDays($historyDays - 1);

            if ($start->lt($earliest)) {
                $start = $earliest;
            }
        }

        if ($start->diffInDays($end) > 366) {
            $start = $end->copy()->subDays(366);
        }

        return [$start, $end];
    }

    public function overview(array $profileIds, Carbon $start, Carbon $end): array
    {
        $rows = AnalyticsDailySummary::query()
            ->whereIn('profile_id', $profileIds)
            ->where('block_id', 0)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('date')
            ->orderBy('date')
            ->get([
                'date',
                DB::raw('SUM(views) as views'),
                DB::raw('SUM(unique_visitors) as uniques'),
                DB::raw('SUM(clicks) as clicks'),
            ])
            ->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        $series = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $row = $rows->get($day->toDateString());
            $series[] = [
                'date' => $day->toDateString(),
                'views' => (int) ($row->views ?? 0),
                'uniques' => (int) ($row->uniques ?? 0),
                'clicks' => (int) ($row->clicks ?? 0),
            ];
        }

        $views = array_sum(array_column($series, 'views'));
        $clicks = array_sum(array_column($series, 'clicks'));

        return [
            'totals' => [
                'views' => $views,
                'uniques' => array_sum(array_column($series, 'uniques')),
                'clicks' => $clicks,
                'ctr' => $views > 0 ? round($clicks / $views * 100, 1) : 0.0,
            ],
            'series' => $series,
        ];
    }

    /** Top clicked blocks in range, labelled with their current (or last known) title. */
    public function topBlocks(array $profileIds, Carbon $start, Carbon $end, int $limit = 10): array
    {
        $rows = AnalyticsDailySummary::query()
            ->whereIn('profile_id', $profileIds)
            ->where('block_id', '>', 0)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('block_id', 'profile_id')
            ->orderByDesc(DB::raw('SUM(clicks)'))
            ->limit($limit)
            ->get(['block_id', 'profile_id', DB::raw('SUM(clicks) as clicks')]);

        $blocks = ProfileBlock::query()->whereIn('id', $rows->pluck('block_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($blocks) {
            $block = $blocks->get($row->block_id);
            $label = $block && $this->blocks->has($block->type)
                ? ($this->blocks->get($block->type)->summary($block->data ?? []) ?: $this->blocks->get($block->type)->name())
                : __('analytics.deleted_block');

            return ['block_id' => (int) $row->block_id, 'profile_id' => (int) $row->profile_id, 'label' => $label, 'clicks' => (int) $row->clicks, 'type' => $block?->type];
        })->all();
    }

    /** Detailed breakdown by referrer / device / browser / country (needs analytics.detailed). */
    public function breakdown(array $profileIds, Carbon $start, Carbon $end, string $dimension, int $limit = 10): array
    {
        if (! in_array($dimension, ['referrer_host', 'device', 'browser', 'country'], true)) {
            return [];
        }

        [$fromUtc, $toUtc] = $this->utcBounds($start, $end);

        return AnalyticsEvent::query()
            ->whereIn('profile_id', $profileIds)
            ->whereBetween('created_at', [$fromUtc, $toUtc])
            ->groupBy($dimension)
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit($limit)
            ->get([
                $dimension.' as label',
                DB::raw("SUM(CASE WHEN type = 'view' THEN 1 ELSE 0 END) as views"),
                DB::raw("SUM(CASE WHEN type = 'click' THEN 1 ELSE 0 END) as clicks"),
            ])
            ->map(fn ($r) => ['label' => $r->label, 'views' => (int) $r->views, 'clicks' => (int) $r->clicks])
            ->all();
    }

    /** Weekly / monthly roll-up of a daily series. */
    public function rollup(array $series, string $period): array
    {
        $buckets = [];

        foreach ($series as $day) {
            $date = Carbon::parse($day['date']);
            $key = $period === 'month' ? $date->format('Y-m') : $date->copy()->startOfWeek()->toDateString();
            $buckets[$key] ??= ['period' => $key, 'views' => 0, 'uniques' => 0, 'clicks' => 0];
            $buckets[$key]['views'] += $day['views'];
            $buckets[$key]['uniques'] += $day['uniques'];
            $buckets[$key]['clicks'] += $day['clicks'];
        }

        return array_values($buckets);
    }

    /** Streams CSV rows: one line per day plus per-block click totals. */
    public function csvRows(array $profileIds, Carbon $start, Carbon $end): \Generator
    {
        yield [__('analytics.csv.date'), __('analytics.csv.views'), __('analytics.csv.unique_visitors'), __('analytics.csv.clicks'), __('analytics.csv.ctr')];

        foreach ($this->overview($profileIds, $start, $end)['series'] as $day) {
            yield [$day['date'], $day['views'], $day['uniques'], $day['clicks'], $day['views'] ? round($day['clicks'] / $day['views'] * 100, 1) : 0];
        }

        yield [];
        yield [__('analytics.csv.link'), __('analytics.csv.clicks')];

        foreach ($this->topBlocks($profileIds, $start, $end, 500) as $block) {
            yield [$block['label'], $block['clicks']];
        }
    }

    /** Platform-wide totals for the superadmin dashboard. */
    public function platformTotals(Carbon $start, Carbon $end): array
    {
        $row = AnalyticsDailySummary::query()
            ->where('block_id', 0)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->first([DB::raw('COALESCE(SUM(views),0) as views'), DB::raw('COALESCE(SUM(clicks),0) as clicks'), DB::raw('COALESCE(SUM(unique_visitors),0) as uniques')]);

        return ['views' => (int) $row->views, 'clicks' => (int) $row->clicks, 'uniques' => (int) $row->uniques];
    }

    public function topProfiles(Carbon $start, Carbon $end, int $limit = 10): array
    {
        return AnalyticsDailySummary::query()
            ->join('profiles', 'profiles.id', '=', 'analytics_daily_summaries.profile_id')
            ->where('analytics_daily_summaries.block_id', 0)
            ->whereBetween('analytics_daily_summaries.date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('analytics_daily_summaries.profile_id', 'profiles.slug', 'profiles.display_name')
            ->orderByDesc(DB::raw('SUM(analytics_daily_summaries.views)'))
            ->limit($limit)
            ->get(['analytics_daily_summaries.profile_id', 'profiles.slug', 'profiles.display_name', DB::raw('SUM(analytics_daily_summaries.views) as views'), DB::raw('SUM(analytics_daily_summaries.clicks) as clicks')])
            ->map(fn ($r) => ['profile_id' => $r->profile_id, 'slug' => $r->slug, 'name' => $r->display_name, 'views' => (int) $r->views, 'clicks' => (int) $r->clicks])
            ->all();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function utcBounds(Carbon $start, Carbon $end): array
    {
        $tz = $this->timezone();

        return [
            Carbon::parse($start->toDateString(), $tz)->startOfDay()->utc(),
            Carbon::parse($end->toDateString(), $tz)->endOfDay()->utc(),
        ];
    }

    private function parseDate(?string $value, string $tz): ?Carbon
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value, $tz)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function timezone(): string
    {
        return (string) ($this->settings->get('site.timezone') ?: 'UTC');
    }
}
