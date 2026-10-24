<?php

namespace App\Support;

/**
 * Providers whose tags the current response may load. Filled while the page renders
 * (partials/tracking) and read by SecurityHeaders to widen the CSP for this page only.
 */
final class TrackingContext
{
    /** @var array<string, true> */
    private array $providers = [];

    public function allow(array $providers): void
    {
        foreach ($providers as $provider) {
            if (isset(Tracking::CSP[$provider])) {
                $this->providers[$provider] = true;
            }
        }
    }

    /** @return array<string, list<string>> directive => extra hosts */
    public function cspAdditions(): array
    {
        $additions = [];

        foreach (array_keys($this->providers) as $provider) {
            foreach (Tracking::CSP[$provider] as $directive => $hosts) {
                $additions[$directive] = array_values(array_unique(array_merge($additions[$directive] ?? [], $hosts)));
            }
        }

        return $additions;
    }
}
