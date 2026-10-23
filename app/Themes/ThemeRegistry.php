<?php

namespace App\Themes;

use App\Models\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Theme designs are defined in code; the `themes` table only controls availability
 * and tier. Adding a theme never changes how user data is stored: profiles keep
 * `theme_key` + a sparse `theme_settings` array of overrides.
 */
class ThemeRegistry
{
    public const FALLBACK = 'minimal-light';

    public const FONTS = [
        'inter' => ['label' => 'Inter', 'family' => "'Inter Variable', system-ui, sans-serif"],
        'dm-sans' => ['label' => 'DM Sans', 'family' => "'DM Sans Variable', system-ui, sans-serif"],
        'space-grotesk' => ['label' => 'Space Grotesk', 'family' => "'Space Grotesk Variable', system-ui, sans-serif"],
        'outfit' => ['label' => 'Outfit', 'family' => "'Outfit Variable', system-ui, sans-serif"],
        'playfair' => ['label' => 'Playfair Display', 'family' => "'Playfair Display Variable', Georgia, serif"],
        'fraunces' => ['label' => 'Fraunces', 'family' => "'Fraunces Variable', Georgia, serif"],
        'jetbrains-mono' => ['label' => 'JetBrains Mono', 'family' => "'JetBrains Mono Variable', ui-monospace, monospace"],
        'system' => ['label' => 'System', 'family' => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'],
    ];

    /** @return array<string, array> */
    public function definitions(): array
    {
        return [
            'minimal-light' => [
                'name' => ['tr' => 'Minimal Açık', 'en' => 'Minimal Light'],
                'description' => ['tr' => 'Bol beyaz alan, ince çizgili kapsül butonlar.', 'en' => 'Plenty of white space and thin outlined pill buttons.'],
                'premium' => false,
                'layout' => 'centered',
                'defaults' => [
                    'mode' => 'light', 'bg_type' => 'solid', 'button_style' => 'outline', 'button_radius' => 999,
                    'button_border_width' => 1, 'button_shadow' => 'none', 'card_style' => 'flat', 'avatar_size' => 96,
                    'avatar_shape' => 'circle', 'banner_style' => 'none', 'font' => 'inter', 'heading_font' => 'inter',
                    'font_size' => 16, 'icon_style' => 'plain', 'page_width' => 'narrow', 'align' => 'center',
                    'spacing' => 'normal', 'animation' => 'subtle',
                ],
                'palettes' => [
                    'light' => ['bg_color' => '#FFFFFF', 'text_color' => '#111111', 'muted_color' => '#6B7280', 'button_bg' => '#FFFFFF', 'button_text' => '#111111', 'button_border_color' => '#111111', 'card_bg' => '#F6F6F6'],
                    'dark' => ['bg_color' => '#111111', 'text_color' => '#F5F5F5', 'muted_color' => '#A1A1AA', 'button_bg' => '#111111', 'button_text' => '#F5F5F5', 'button_border_color' => '#F5F5F5', 'card_bg' => '#1C1C1C'],
                ],
            ],
            'dark-pro' => [
                'name' => ['tr' => 'Dark Pro', 'en' => 'Dark Pro'],
                'description' => ['tr' => 'Koyu zemin, sarı vurgu, keskin dolu butonlar.', 'en' => 'Dark canvas, yellow accent and sharp solid buttons.'],
                'premium' => false,
                'layout' => 'centered',
                'defaults' => [
                    'mode' => 'dark', 'bg_type' => 'solid', 'button_style' => 'fill', 'button_radius' => 10,
                    'button_border_width' => 0, 'button_shadow' => 'none', 'card_style' => 'outlined', 'avatar_size' => 104,
                    'avatar_shape' => 'rounded', 'banner_style' => 'none', 'font' => 'space-grotesk', 'heading_font' => 'space-grotesk',
                    'font_size' => 16, 'icon_style' => 'filled', 'page_width' => 'medium', 'align' => 'center',
                    'spacing' => 'normal', 'animation' => 'subtle',
                ],
                'palettes' => [
                    'dark' => ['bg_color' => '#0B0B0F', 'text_color' => '#FAFAFA', 'muted_color' => '#9CA3AF', 'button_bg' => '#FFCF00', 'button_text' => '#111111', 'button_border_color' => '#FFCF00', 'card_bg' => '#16161D'],
                    'light' => ['bg_color' => '#F4F4F5', 'text_color' => '#0B0B0F', 'muted_color' => '#52525B', 'button_bg' => '#0B0B0F', 'button_text' => '#FFCF00', 'button_border_color' => '#0B0B0F', 'card_bg' => '#FFFFFF'],
                ],
            ],
            'creator' => [
                'name' => ['tr' => 'Creator', 'en' => 'Creator'],
                'description' => ['tr' => 'Kalın çerçeveler ve sert gölgelerle enerjik, oyuncu bir görünüm.', 'en' => 'Bold borders and hard shadows for a playful, energetic look.'],
                'premium' => false,
                'layout' => 'centered',
                'defaults' => [
                    'mode' => 'light', 'bg_type' => 'solid', 'button_style' => 'fill', 'button_radius' => 14,
                    'button_border_width' => 2, 'button_shadow' => 'hard', 'card_style' => 'outlined', 'avatar_size' => 112,
                    'avatar_shape' => 'circle', 'banner_style' => 'contained', 'font' => 'outfit', 'heading_font' => 'outfit',
                    'font_size' => 17, 'icon_style' => 'filled', 'page_width' => 'medium', 'align' => 'center',
                    'spacing' => 'relaxed', 'animation' => 'lively',
                ],
                'palettes' => [
                    'light' => ['bg_color' => '#FFF4E0', 'text_color' => '#1A1A1A', 'muted_color' => '#57534E', 'button_bg' => '#FF8A5B', 'button_text' => '#1A1A1A', 'button_border_color' => '#1A1A1A', 'card_bg' => '#FFFFFF'],
                    'dark' => ['bg_color' => '#1A1A1A', 'text_color' => '#FFF4E0', 'muted_color' => '#D6D3D1', 'button_bg' => '#FF8A5B', 'button_text' => '#1A1A1A', 'button_border_color' => '#FFF4E0', 'card_bg' => '#262626'],
                ],
            ],
            'business' => [
                'name' => ['tr' => 'Business', 'en' => 'Business'],
                'description' => ['tr' => 'Kurumsal kapak bandı, sola hizalı kimlik, sade dikdörtgen butonlar.', 'en' => 'Corporate cover band, left-aligned identity, clean rectangular buttons.'],
                'premium' => true,
                'layout' => 'business',
                'defaults' => [
                    'mode' => 'light', 'bg_type' => 'solid', 'button_style' => 'soft', 'button_radius' => 6,
                    'button_border_width' => 1, 'button_shadow' => 'none', 'card_style' => 'outlined', 'avatar_size' => 96,
                    'avatar_shape' => 'rounded', 'banner_style' => 'cover', 'font' => 'dm-sans', 'heading_font' => 'dm-sans',
                    'font_size' => 16, 'icon_style' => 'outline', 'page_width' => 'wide', 'align' => 'left',
                    'spacing' => 'normal', 'animation' => 'none',
                ],
                'palettes' => [
                    'light' => ['bg_color' => '#F8FAFC', 'text_color' => '#0F172A', 'muted_color' => '#475569', 'button_bg' => '#FFFFFF', 'button_text' => '#0F172A', 'button_border_color' => '#CBD5E1', 'card_bg' => '#FFFFFF'],
                    'dark' => ['bg_color' => '#0F172A', 'text_color' => '#F1F5F9', 'muted_color' => '#94A3B8', 'button_bg' => '#1E293B', 'button_text' => '#F1F5F9', 'button_border_color' => '#334155', 'card_bg' => '#1E293B'],
                ],
            ],
            'portfolio' => [
                'name' => ['tr' => 'Portfolio', 'en' => 'Portfolio'],
                'description' => ['tr' => 'Bağlantılar küçük görselli iki sütunlu kartlarda; işlerini sergile.', 'en' => 'Links as two-column cards with thumbnails to showcase your work.'],
                'premium' => true,
                'layout' => 'grid',
                'defaults' => [
                    'mode' => 'light', 'bg_type' => 'solid', 'button_style' => 'soft', 'button_radius' => 16,
                    'button_border_width' => 0, 'button_shadow' => 'soft', 'card_style' => 'elevated', 'avatar_size' => 88,
                    'avatar_shape' => 'circle', 'banner_style' => 'contained', 'font' => 'dm-sans', 'heading_font' => 'fraunces',
                    'font_size' => 16, 'icon_style' => 'outline', 'page_width' => 'wide', 'align' => 'left',
                    'spacing' => 'normal', 'animation' => 'subtle',
                ],
                'palettes' => [
                    'light' => ['bg_color' => '#FAFAF9', 'text_color' => '#1C1917', 'muted_color' => '#78716C', 'button_bg' => '#FFFFFF', 'button_text' => '#1C1917', 'button_border_color' => '#E7E5E4', 'card_bg' => '#FFFFFF'],
                    'dark' => ['bg_color' => '#1C1917', 'text_color' => '#FAFAF9', 'muted_color' => '#A8A29E', 'button_bg' => '#292524', 'button_text' => '#FAFAF9', 'button_border_color' => '#44403C', 'card_bg' => '#292524'],
                ],
            ],
            'neon' => [
                'name' => ['tr' => 'Neon', 'en' => 'Neon'],
                'description' => ['tr' => 'Siyah zeminde parlayan neon çerçeveler ve büyük harfli tipografi.', 'en' => 'Glowing neon outlines and uppercase type on black.'],
                'premium' => true,
                'layout' => 'centered',
                'defaults' => [
                    'mode' => 'dark', 'bg_type' => 'solid', 'button_style' => 'outline', 'button_radius' => 4,
                    'button_border_width' => 2, 'button_shadow' => 'glow', 'card_style' => 'outlined', 'avatar_size' => 108,
                    'avatar_shape' => 'square', 'banner_style' => 'none', 'font' => 'space-grotesk', 'heading_font' => 'space-grotesk',
                    'font_size' => 15, 'icon_style' => 'outline', 'page_width' => 'medium', 'align' => 'center',
                    'spacing' => 'relaxed', 'animation' => 'lively',
                ],
                'palettes' => [
                    'dark' => ['bg_color' => '#050505', 'text_color' => '#E5FFE0', 'muted_color' => '#86EFAC', 'button_bg' => '#050505', 'button_text' => '#39FF14', 'button_border_color' => '#39FF14', 'card_bg' => '#0A0F0A'],
                    'light' => ['bg_color' => '#F5F0FF', 'text_color' => '#2E1065', 'muted_color' => '#6D28D9', 'button_bg' => '#F5F0FF', 'button_text' => '#7C3AED', 'button_border_color' => '#A855F7', 'card_bg' => '#FFFFFF'],
                ],
            ],
            'glass' => [
                'name' => ['tr' => 'Glass', 'en' => 'Glass'],
                'description' => ['tr' => 'Renkli degrade üzerinde buzlu cam kartlar.', 'en' => 'Frosted glass cards over a colourful gradient.'],
                'premium' => true,
                'layout' => 'centered',
                'defaults' => [
                    'mode' => 'dark', 'bg_type' => 'gradient', 'bg_gradient_angle' => 145, 'button_style' => 'glass', 'button_radius' => 18,
                    'button_border_width' => 1, 'button_shadow' => 'soft', 'card_style' => 'glass', 'avatar_size' => 112,
                    'avatar_shape' => 'circle', 'banner_style' => 'none', 'font' => 'outfit', 'heading_font' => 'outfit',
                    'font_size' => 16, 'icon_style' => 'plain', 'page_width' => 'narrow', 'align' => 'center',
                    'spacing' => 'normal', 'animation' => 'subtle',
                ],
                'palettes' => [
                    'dark' => ['bg_color' => '#312E81', 'bg_gradient_from' => '#4F46E5', 'bg_gradient_to' => '#DB2777', 'text_color' => '#FFFFFF', 'muted_color' => '#E0E7FF', 'button_bg' => '#FFFFFF', 'button_text' => '#FFFFFF', 'button_border_color' => '#FFFFFF', 'card_bg' => '#FFFFFF'],
                    'light' => ['bg_color' => '#E0F2FE', 'bg_gradient_from' => '#BAE6FD', 'bg_gradient_to' => '#FBCFE8', 'text_color' => '#0C4A6E', 'muted_color' => '#0369A1', 'button_bg' => '#FFFFFF', 'button_text' => '#0C4A6E', 'button_border_color' => '#FFFFFF', 'card_bg' => '#FFFFFF'],
                ],
            ],
            'editorial' => [
                'name' => ['tr' => 'Editorial', 'en' => 'Editorial'],
                'description' => ['tr' => 'Dergi sayfası gibi: serif başlıklar, oklu liste satırları, buton yok.', 'en' => 'Like a magazine page: serif headlines, arrow list rows, no boxes.'],
                'premium' => true,
                'layout' => 'editorial',
                'defaults' => [
                    'mode' => 'light', 'bg_type' => 'solid', 'button_style' => 'underline', 'button_radius' => 0,
                    'button_border_width' => 1, 'button_shadow' => 'none', 'card_style' => 'flat', 'avatar_size' => 80,
                    'avatar_shape' => 'square', 'banner_style' => 'contained', 'font' => 'inter', 'heading_font' => 'fraunces',
                    'font_size' => 17, 'icon_style' => 'plain', 'page_width' => 'medium', 'align' => 'left',
                    'spacing' => 'relaxed', 'animation' => 'none',
                ],
                'palettes' => [
                    'light' => ['bg_color' => '#F7F3EA', 'text_color' => '#1F1A14', 'muted_color' => '#6B6152', 'button_bg' => '#F7F3EA', 'button_text' => '#1F1A14', 'button_border_color' => '#1F1A14', 'card_bg' => '#EFE8DA'],
                    'dark' => ['bg_color' => '#1F1A14', 'text_color' => '#F7F3EA', 'muted_color' => '#B8AD9A', 'button_bg' => '#1F1A14', 'button_text' => '#F7F3EA', 'button_border_color' => '#F7F3EA', 'card_bg' => '#2B241B'],
                ],
            ],
            'gradient' => [
                'name' => ['tr' => 'Gradient', 'en' => 'Gradient'],
                'description' => ['tr' => 'Canlı gün batımı degradesi ve beyaz kapsül butonlar.', 'en' => 'Vivid sunset gradient with white pill buttons.'],
                'premium' => false,
                'layout' => 'centered',
                'defaults' => [
                    'mode' => 'light', 'bg_type' => 'gradient', 'bg_gradient_angle' => 160, 'button_style' => 'fill', 'button_radius' => 999,
                    'button_border_width' => 0, 'button_shadow' => 'soft', 'card_style' => 'elevated', 'avatar_size' => 104,
                    'avatar_shape' => 'circle', 'banner_style' => 'none', 'font' => 'outfit', 'heading_font' => 'outfit',
                    'font_size' => 16, 'icon_style' => 'filled', 'page_width' => 'narrow', 'align' => 'center',
                    'spacing' => 'normal', 'animation' => 'subtle',
                ],
                'palettes' => [
                    'light' => ['bg_color' => '#F97316', 'bg_gradient_from' => '#F59E0B', 'bg_gradient_to' => '#DB2777', 'text_color' => '#FFFFFF', 'muted_color' => '#FFEDD5', 'button_bg' => '#FFFFFF', 'button_text' => '#9D174D', 'button_border_color' => '#FFFFFF', 'card_bg' => '#FFFFFF'],
                    'dark' => ['bg_color' => '#1E1B4B', 'bg_gradient_from' => '#0F172A', 'bg_gradient_to' => '#6D28D9', 'text_color' => '#FFFFFF', 'muted_color' => '#DDD6FE', 'button_bg' => '#FFFFFF', 'button_text' => '#4C1D95', 'button_border_color' => '#FFFFFF', 'card_bg' => '#FFFFFF'],
                ],
            ],
            'elegant' => [
                'name' => ['tr' => 'Elegant', 'en' => 'Elegant'],
                'description' => ['tr' => 'Derin yeşil ve altın tonları, ince çizgiler, geniş harf aralığı.', 'en' => 'Deep green and gold, hairline borders and wide letter spacing.'],
                'premium' => true,
                'layout' => 'centered',
                'defaults' => [
                    'mode' => 'dark', 'bg_type' => 'solid', 'button_style' => 'outline', 'button_radius' => 2,
                    'button_border_width' => 1, 'button_shadow' => 'none', 'card_style' => 'outlined', 'avatar_size' => 120,
                    'avatar_shape' => 'circle', 'banner_style' => 'none', 'font' => 'inter', 'heading_font' => 'playfair',
                    'font_size' => 15, 'icon_style' => 'outline', 'page_width' => 'narrow', 'align' => 'center',
                    'spacing' => 'relaxed', 'animation' => 'subtle',
                ],
                'palettes' => [
                    'dark' => ['bg_color' => '#0E1F1A', 'text_color' => '#F3EAD3', 'muted_color' => '#C9B98F', 'button_bg' => '#0E1F1A', 'button_text' => '#E6C989', 'button_border_color' => '#C9A75A', 'card_bg' => '#132A23'],
                    'light' => ['bg_color' => '#FBF8F1', 'text_color' => '#1F2D27', 'muted_color' => '#6B5B3A', 'button_bg' => '#FBF8F1', 'button_text' => '#1F2D27', 'button_border_color' => '#B8955A', 'card_bg' => '#FFFFFF'],
                ],
            ],
            'sunny' => [
                'name' => ['tr' => 'Sunny', 'en' => 'Sunny'],
                'description' => ['tr' => 'Biofy sarısı zemin, siyah dolu butonlar: imza görünüm.', 'en' => 'Biofy yellow canvas with black solid buttons: the signature look.'],
                'premium' => false,
                'layout' => 'centered',
                'defaults' => [
                    'mode' => 'light', 'bg_type' => 'solid', 'button_style' => 'fill', 'button_radius' => 12,
                    'button_border_width' => 0, 'button_shadow' => 'none', 'card_style' => 'flat', 'avatar_size' => 104,
                    'avatar_shape' => 'circle', 'banner_style' => 'none', 'font' => 'inter', 'heading_font' => 'inter',
                    'font_size' => 16, 'icon_style' => 'filled', 'page_width' => 'narrow', 'align' => 'center',
                    'spacing' => 'normal', 'animation' => 'subtle',
                ],
                'palettes' => [
                    'light' => ['bg_color' => '#FFCF00', 'text_color' => '#111111', 'muted_color' => '#3F3A1E', 'button_bg' => '#111111', 'button_text' => '#FFFFFF', 'button_border_color' => '#111111', 'card_bg' => '#FFE066'],
                    'dark' => ['bg_color' => '#111111', 'text_color' => '#FFCF00', 'muted_color' => '#D4D4D4', 'button_bg' => '#FFCF00', 'button_text' => '#111111', 'button_border_color' => '#FFCF00', 'card_bg' => '#1F1F1F'],
                ],
            ],
            'mono' => [
                'name' => ['tr' => 'Terminal', 'en' => 'Terminal'],
                'description' => ['tr' => 'Geliştiriciler için tek aralıklı yazı ve komut satırı estetiği.', 'en' => 'Monospaced type and a command-line aesthetic for developers.'],
                'premium' => true,
                'layout' => 'editorial',
                'defaults' => [
                    'mode' => 'dark', 'bg_type' => 'solid', 'button_style' => 'outline', 'button_radius' => 0,
                    'button_border_width' => 1, 'button_shadow' => 'none', 'card_style' => 'outlined', 'avatar_size' => 72,
                    'avatar_shape' => 'square', 'banner_style' => 'none', 'font' => 'jetbrains-mono', 'heading_font' => 'jetbrains-mono',
                    'font_size' => 14, 'icon_style' => 'plain', 'page_width' => 'medium', 'align' => 'left',
                    'spacing' => 'compact', 'animation' => 'none',
                ],
                'palettes' => [
                    'dark' => ['bg_color' => '#0D1117', 'text_color' => '#C9D1D9', 'muted_color' => '#8B949E', 'button_bg' => '#0D1117', 'button_text' => '#7EE787', 'button_border_color' => '#30363D', 'card_bg' => '#161B22'],
                    'light' => ['bg_color' => '#FFFFFF', 'text_color' => '#24292F', 'muted_color' => '#57606A', 'button_bg' => '#FFFFFF', 'button_text' => '#0550AE', 'button_border_color' => '#D0D7DE', 'card_bg' => '#F6F8FA'],
                ],
            ],
        ];
    }

    public function has(string $key): bool
    {
        return isset($this->definitions()[$key]);
    }

    public function get(string $key): array
    {
        $definitions = $this->definitions();

        return ['key' => $key] + ($definitions[$key] ?? $definitions[self::FALLBACK]);
    }

    /** Availability rows from the DB (active/premium flags), keyed by theme key. */
    public function availability(): array
    {
        try {
            return Cache::rememberForever('biofy.themes.availability', function () {
                if (! Schema::hasTable('themes')) {
                    return [];
                }

                return Theme::query()->get()->keyBy('key')->map(fn (Theme $t) => [
                    'is_active' => $t->is_active,
                    'is_premium' => $t->is_premium,
                    'sort_order' => $t->sort_order,
                ])->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    public function flush(): void
    {
        Cache::forget('biofy.themes.availability');
    }

    public function isActive(string $key): bool
    {
        return $this->has($key) && ($this->availability()[$key]['is_active'] ?? true);
    }

    public function isPremium(string $key): bool
    {
        return (bool) ($this->availability()[$key]['is_premium'] ?? $this->definitions()[$key]['premium'] ?? false);
    }

    /** Active themes in display order. */
    public function active(): array
    {
        $availability = $this->availability();
        $themes = [];

        foreach ($this->definitions() as $key => $definition) {
            if ($this->isActive($key)) {
                $themes[$key] = $this->get($key) + ['is_premium' => $this->isPremium($key), 'sort' => $availability[$key]['sort_order'] ?? 0];
            }
        }

        uasort($themes, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return $themes;
    }
}
