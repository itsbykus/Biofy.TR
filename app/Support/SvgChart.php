<?php

namespace App\Support;

/**
 * Server-rendered SVG charts (no JavaScript charting library). Geometry only;
 * colours come from CSS classes so the CSP needs no inline styles.
 */
final class SvgChart
{
    /**
     * @param  list<array{label: string, values: array<string, int|float>}>  $points
     * @param  list<string>  $series  keys of values to draw
     * @return array{width: int, height: int, paths: array<string, string>, areas: array<string, string>, max: float, ticks: list<array{y: float, value: float}>, labels: list<array{x: float, text: string}>}
     */
    public static function lines(array $points, array $series, int $width = 720, int $height = 220): array
    {
        $padLeft = 36;
        $padBottom = 24;
        $padTop = 10;
        $innerW = $width - $padLeft - 8;
        $innerH = $height - $padBottom - $padTop;
        $count = count($points);
        $max = 0.0;

        foreach ($points as $p) {
            foreach ($series as $s) {
                $max = max($max, (float) ($p['values'][$s] ?? 0));
            }
        }

        $max = self::niceMax($max);
        $x = fn (int $i) => $padLeft + ($count <= 1 ? $innerW / 2 : $i * $innerW / ($count - 1));
        $y = fn (float $v) => $padTop + $innerH - ($max > 0 ? $v / $max * $innerH : 0);

        $paths = [];
        $areas = [];

        foreach ($series as $s) {
            $d = '';

            foreach ($points as $i => $p) {
                $d .= ($i === 0 ? 'M' : 'L').round($x($i), 1).','.round($y((float) ($p['values'][$s] ?? 0)), 1);
            }

            $paths[$s] = $d;
            $areas[$s] = $count > 0 ? $d.'L'.round($x($count - 1), 1).','.($padTop + $innerH).'L'.round($x(0), 1).','.($padTop + $innerH).'Z' : '';
        }

        $ticks = [];

        foreach ([0, 0.5, 1] as $f) {
            $ticks[] = ['y' => round($y($max * $f), 1), 'value' => $max * $f];
        }

        $labels = [];
        $step = max(1, (int) ceil($count / 6));

        foreach ($points as $i => $p) {
            if ($i % $step === 0 || $i === $count - 1) {
                $labels[] = ['x' => round($x($i), 1), 'text' => $p['label']];
            }
        }

        return compact('width', 'height', 'paths', 'areas', 'max', 'ticks', 'labels') + ['baseline' => $padTop + $innerH, 'left' => $padLeft];
    }

    private static function niceMax(float $max): float
    {
        if ($max <= 4) {
            return 4;
        }

        $magnitude = 10 ** floor(log10($max));

        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($m * $magnitude >= $max) {
                return $m * $magnitude;
            }
        }

        return 10 * $magnitude;
    }
}
