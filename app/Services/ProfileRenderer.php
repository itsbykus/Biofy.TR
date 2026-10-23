<?php

namespace App\Services;

use App\Blocks\BlockRegistry;
use App\Models\Profile;
use App\Themes\ThemeRegistry;
use App\Themes\ThemeSettings;
use Illuminate\Support\Carbon;

/**
 * Turns a published snapshot (or a live draft snapshot for previews) into view
 * data. Entitlements are re-applied at render time so a downgrade takes effect
 * immediately even on pages published while the user had a higher plan.
 */
class ProfileRenderer
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly ThemeRegistry $themes,
        private readonly Entitlements $entitlements,
        private readonly Settings $settings,
    ) {
    }

    public function prepare(Profile $profile, array $snapshot, bool $preview = false, bool $onCustomDomain = false): array
    {
        $owner = $profile->user;
        $now = now();

        $themeKey = $snapshot['theme']['key'] ?? ThemeRegistry::FALLBACK;
        $themeSettings = $snapshot['theme']['settings'] ?? [];

        if (! $this->themes->isActive($themeKey)
            || ($this->themes->isPremium($themeKey) && ! $this->entitlements->allows($owner, Entitlements::PREMIUM_THEMES))) {
            $themeKey = ThemeRegistry::FALLBACK;
            $themeSettings = array_intersect_key($themeSettings, array_filter(ThemeSettings::fields(), fn ($f) => $f['tier'] === 'basic'));
        }

        if (! $this->entitlements->allows($owner, Entitlements::ADVANCED_CUSTOMIZATION)) {
            $themeSettings = array_intersect_key($themeSettings, array_filter(ThemeSettings::fields(), fn ($f) => $f['tier'] === 'basic'));
        }

        $theme = $this->themes->get($themeKey);
        $resolved = ThemeSettings::resolve($theme, $themeSettings);

        $advancedBlocks = $this->entitlements->allows($owner, Entitlements::ADVANCED_BLOCKS);
        $maxLinks = $this->entitlements->limit($owner, Entitlements::MAX_LINKS);
        $linksShown = 0;
        $blocks = [];

        foreach ($snapshot['blocks'] ?? [] as $block) {
            if (! $this->registry->has($block['type'])) {
                continue;
            }

            $type = $this->registry->get($block['type']);

            if ($type->isAdvanced() && ! $advancedBlocks) {
                continue;
            }

            // Scheduled visibility is evaluated per request: no cron needed for start/expiry.
            if (! empty($block['starts_at']) && Carbon::parse($block['starts_at'])->isAfter($now)) {
                continue;
            }

            if (! empty($block['ends_at']) && Carbon::parse($block['ends_at'])->lte($now)) {
                continue;
            }

            if ($type->countsAsLink()) {
                if ($maxLinks !== Entitlements::UNLIMITED && $linksShown >= $maxLinks) {
                    continue;
                }

                $linksShown++;
            }

            $blocks[] = $this->withHrefs($block, $type, $profile, $preview, $onCustomDomain);
        }

        $p = $snapshot['profile'];
        $siteName = (string) $this->settings->get('site.name');
        $title = $p['seo_title'] ?: $p['display_name'].' | '.$siteName;

        return [
            'profile' => $profile,
            'p' => $p,
            'theme' => $theme,
            'settings' => $resolved,
            'cssVariables' => ThemeSettings::cssVariables($resolved, $snapshot['theme']['bg_image'] ?? null),
            'fonts' => ThemeSettings::fontsUsed($resolved),
            'blocks' => $blocks,
            'socialLinks' => $snapshot['social_links'] ?? [],
            'showBranding' => ! $this->entitlements->allows($owner, Entitlements::REMOVE_BRANDING),
            'verified' => $profile->verified_at !== null,
            'preview' => $preview,
            'seo' => [
                'title' => $title,
                'description' => $p['seo_description'] ?: ($p['bio_short'] ?: $p['title'] ?: ''),
                'image' => $p['seo_image'] ?: $p['avatar'],
                'canonical' => $profile->canonicalUrl(),
                'noindex' => $preview || (bool) $p['seo_noindex'] || ! $this->settings->bool('seo.allow_indexing'),
            ],
        ];
    }

    /**
     * Adds `href` (and `item_hrefs`) to a block. Public pages go through the tracked
     * /go/... redirect (relative, so it works on customer domains too); previews link
     * straight to the destination and are never counted.
     */
    private function withHrefs(array $block, \App\Blocks\BlockType $type, Profile $profile, bool $preview, bool $onCustomDomain): array
    {
        $view = $block['view'] ?? [];
        $tracked = fn (?int $item) => '/go/'.$profile->id.'/'.$block['id'].($item !== null ? '/'.$item : '');

        if ($block['type'] === 'contact') {
            $block['href'] = $preview ? null : ($onCustomDomain ? '/vcard' : '/'.$profile->slug.'/vcard');

            return $block;
        }

        $target = $type->clickTarget($view);
        $block['href'] = $target === null ? null : ($preview ? $target : $tracked(null));

        if (isset($view['items']) && is_array($view['items'])) {
            $block['item_hrefs'] = [];

            foreach (array_keys($view['items']) as $i) {
                $itemTarget = $type->clickTarget($view, $i);
                $block['item_hrefs'][$i] = $itemTarget === null ? null : ($preview ? $itemTarget : $tracked($i));
            }
        }

        return $block;
    }

    /** Snapshot built from the current draft, used by the editor's live preview. */
    public function draftSnapshot(Profile $profile): array
    {
        return app(ProfileService::class)->buildSnapshot($profile->fresh(['blocks', 'socialLinks', 'user']));
    }
}
