<?php

namespace Tests\Unit;

use App\Support\Icons;
use App\Support\LinkIcons;
use Tests\TestCase;

class LinkIconsTest extends TestCase
{
    public function test_well_known_links_are_recognised(): void
    {
        $cases = [
            'https://www.instagram.com/biofy' => ['brand' => 'instagram'],
            'https://youtu.be/abc' => ['brand' => 'youtube'],
            'https://music.youtube.com/watch?v=1' => ['brand' => 'youtube_music'],
            'https://open.spotify.com/artist/1' => ['brand' => 'spotify'],
            'https://wa.me/905551112233' => ['brand' => 'whatsapp'],
            'https://m.facebook.com/page' => ['brand' => 'facebook'],
            'https://twitter.com/x' => ['brand' => 'x'],
            'https://shop.myshopify.com/' => ['brand' => 'shopify'],
            'https://www.google.com/maps/place/Istanbul' => ['brand' => 'google_maps'],
            'https://maps.app.goo.gl/abc' => ['brand' => 'google_maps'],
            'https://www.airbnb.com.tr/rooms/1' => ['brand' => 'airbnb'],
            'https://www.linkedin.com/in/someone' => ['icon' => 'briefcase'],
            'https://www.amazon.de/dp/1' => ['icon' => 'shopping-cart'],
            'https://www.trendyol.com/x' => ['icon' => 'shopping-cart'],
            'mailto:hi@example.com' => ['icon' => 'mail'],
            'tel:+905551112233' => ['icon' => 'phone'],
        ];

        foreach ($cases as $url => $expected) {
            $this->assertSame($expected, LinkIcons::detect($url), $url);
        }

        // Look-alike hosts are not brands.
        $this->assertNull(LinkIcons::detect('https://notinstagram.com/x'));
        $this->assertNull(LinkIcons::detect('https://instagram.com.evil.example/x'));
        $this->assertNull(LinkIcons::detect('https://example.com'));
    }

    public function test_every_recognised_brand_has_an_icon(): void
    {
        $reflection = new \ReflectionClass(LinkIcons::class);

        foreach (array_merge($reflection->getConstant('HOSTS'), $reflection->getConstant('BRAND_LABELS')) as $value) {
            if (str_starts_with($value, 'icon:')) {
                $this->assertContains(substr($value, 5), LinkIcons::UI, $value);
            } else {
                $this->assertNotNull(Icons::brand($value), $value);
            }
        }

        foreach (LinkIcons::UI as $name) {
            $this->assertNotSame(Icons::ui('square'), Icons::ui($name), "ui icon {$name}");
        }
    }

    public function test_choices_are_validated_and_resolved(): void
    {
        $this->assertSame('', LinkIcons::normalizeChoice(''));
        $this->assertSame('none', LinkIcons::normalizeChoice('none'));
        $this->assertSame('brand:github', LinkIcons::normalizeChoice('brand:github'));
        $this->assertSame('icon:heart', LinkIcons::normalizeChoice('icon:heart'));
        $this->assertNull(LinkIcons::normalizeChoice('brand:not-a-brand'));
        $this->assertNull(LinkIcons::normalizeChoice('icon:"><script>'));

        $this->assertNull(LinkIcons::resolve('none', 'https://instagram.com/x'));
        $this->assertSame(['brand' => 'github'], LinkIcons::resolve('brand:github', 'https://instagram.com/x'));
        $this->assertSame(['icon' => 'heart'], LinkIcons::resolve('icon:heart', null));
        $this->assertSame(['brand' => 'instagram'], LinkIcons::resolve('', 'https://instagram.com/x'));
    }
}
