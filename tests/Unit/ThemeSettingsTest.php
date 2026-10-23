<?php

namespace Tests\Unit;

use App\Themes\ThemeRegistry;
use App\Themes\ThemeSettings;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    public function test_at_least_ten_distinct_themes_exist(): void
    {
        $definitions = (new ThemeRegistry)->definitions();

        $this->assertGreaterThanOrEqual(10, count($definitions));

        // Themes differ structurally, not only by colour.
        $signatures = collect($definitions)->map(fn ($t) => implode('|', [$t['layout'], $t['defaults']['font'], $t['defaults']['heading_font'], $t['defaults']['button_style'], $t['defaults']['button_radius'], $t['defaults']['card_style']]));
        $this->assertSame($signatures->count(), $signatures->unique()->count());
        $this->assertGreaterThanOrEqual(3, collect($definitions)->pluck('layout')->unique()->count());
    }

    public function test_every_theme_resolves_to_a_complete_valid_setting_set(): void
    {
        $registry = new ThemeRegistry;

        foreach (array_keys($registry->definitions()) as $key) {
            foreach (['light', 'dark'] as $mode) {
                $resolved = ThemeSettings::resolve($registry->get($key), ['mode' => $mode]);

                foreach (ThemeSettings::fields() as $field => $definition) {
                    if ($definition['type'] === 'media') {
                        continue;
                    }

                    $this->assertNotNull(ThemeSettings::cleanValue($definition, $resolved[$field]), "{$key}/{$mode}: {$field}");
                }
            }
        }
    }

    public function test_css_injection_is_impossible(): void
    {
        $clean = ThemeSettings::sanitize([
            'bg_color' => 'red;}</style><script>alert(1)</script>',
            'text_color' => '#000000; background:url(javascript:alert(1))',
            'font' => "Inter'; } body { display:none",
            'button_radius' => '9999999',
            'button_style' => 'expression(alert(1))',
            'unknown_key' => 'x',
        ], true);

        $this->assertArrayNotHasKey('bg_color', $clean);
        $this->assertArrayNotHasKey('text_color', $clean);
        $this->assertArrayNotHasKey('font', $clean);
        $this->assertArrayNotHasKey('button_style', $clean);
        $this->assertArrayNotHasKey('unknown_key', $clean);
        $this->assertSame(999, $clean['button_radius']);

        $css = ThemeSettings::cssVariables(ThemeSettings::resolve((new ThemeRegistry)->get('minimal-light'), $clean), '/media/x"); } body{display:none');
        $this->assertStringNotContainsString('<', $css);
        $this->assertStringNotContainsString('}', $css);
    }

    public function test_advanced_fields_are_dropped_without_entitlement(): void
    {
        $clean = ThemeSettings::sanitize(['bg_color' => '#112233', 'font' => 'fraunces', 'heading_font' => 'fraunces', 'card_style' => 'glass', 'align' => 'right', 'icon_color' => 'brand'], false, ['heading_font' => 'inter']);

        $this->assertSame('#112233', $clean['bg_color']);
        $this->assertSame('fraunces', $clean['font'], 'the body font is available to every plan');
        $this->assertSame('right', $clean['align']);
        $this->assertSame('brand', $clean['icon_color']);
        $this->assertSame('inter', $clean['heading_font'], 'existing advanced value is kept, the new one ignored');
        $this->assertArrayNotHasKey('card_style', $clean);
    }
}
