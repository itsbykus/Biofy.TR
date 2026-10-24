<?php

namespace Database\Seeders;

use App\Models\FeatureDefinition;
use Illuminate\Database\Seeder;

class FeatureCatalogSeeder extends Seeder
{
    /**
     * key => [type, group, default (= Basic), [tr name, en name], [tr description, en description]]
     * Integer -1 = unlimited.
     */
    public static function catalog(): array
    {
        return [
            'profile.max_pages' => ['integer', 'profile', '1', ['Bio sayfası sayısı', 'Number of bio pages'], ['Oluşturulabilecek aktif sayfa sayısı.', 'How many active pages can be created.']],
            'profile.max_links' => ['integer', 'profile', '15', ['Sayfa başına bağlantı', 'Links per page'], ['Bağlantı türündeki blokların sayfa başına üst sınırı.', 'Maximum link-type blocks per page.']],
            'profile.max_social_links' => ['integer', 'profile', '6', ['Sosyal medya hesabı', 'Social accounts'], ['Sayfa başına sosyal medya bağlantısı.', 'Social links per page.']],
            'profile.max_blocks' => ['integer', 'profile', '30', ['Sayfa başına blok', 'Blocks per page'], ['Tüm içerik bloklarının sayfa başına üst sınırı.', 'Maximum content blocks per page.']],
            'profile.slug_redirects' => ['boolean', 'profile', '0', ['Eski adres yönlendirmesi', 'Old URL redirect'], ['Kullanıcı adı değişince eski adres yeni sayfaya yönlenir.', 'Old address redirects after a username change.']],
            'profile.custom_theme' => ['boolean', 'design', '0', ['Premium temalar', 'Premium themes'], ['Premium olarak işaretlenmiş temaları kullanma.', 'Use themes marked as premium.']],
            'profile.advanced_customization' => ['boolean', 'design', '0', ['Gelişmiş özelleştirme', 'Advanced customization'], ['Degrade ve görsel arka plan, kart stili, başlık yazı tipi, sayfa düzeni ve animasyon gibi ayarlar.', 'Gradient and image backgrounds, card styles, heading fonts, page layout, animations and more.']],
            'profile.remove_branding' => ['boolean', 'design', '0', ['Biofy markasını gizle', 'Remove Biofy branding'], ['Sayfa altındaki "Biofy ile oluşturuldu" ibaresini kaldırır.', 'Removes the "Made with Biofy" footer.']],
            'profile.advanced_blocks' => ['boolean', 'content', '0', ['Gelişmiş bloklar', 'Advanced blocks'], ['Video, müzik, galeri, ürün, SSS, harita, bağış, randevu ve bağlantı grubu blokları.', 'Video, music, gallery, product, FAQ, map, donation, booking and link group blocks.']],
            'profile.scheduled_links' => ['boolean', 'content', '0', ['Zamanlanmış bağlantılar', 'Scheduled links'], ['Blokların belirli tarihlerde yayınlanması ve süresinin dolması.', 'Publish blocks at a date and let them expire.']],
            'profile.custom_domain' => ['boolean', 'domains', '0', ['Özel alan adı', 'Custom domain'], ['Kendi alan adını sayfana bağlama.', 'Connect your own domain to a page.']],
            'domain.max_domains' => ['integer', 'domains', '0', ['Alan adı sayısı', 'Number of domains'], ['Bağlanabilecek özel alan adı sayısı.', 'How many custom domains can be connected.']],
            'storage.quota_mb' => ['integer', 'storage', '25', ['Depolama (MB)', 'Storage (MB)'], ['Yüklenen görseller için toplam alan.', 'Total space for uploaded images.']],
            'analytics.detailed' => ['boolean', 'analytics', '0', ['Detaylı analitik', 'Detailed analytics'], ['Kaynaklar, cihaz, tarayıcı, ülke ve eğilimler.', 'Referrers, devices, browsers, countries and trends.']],
            'analytics.export' => ['boolean', 'analytics', '0', ['CSV dışa aktarma', 'CSV export'], ['Analitik verilerini CSV olarak indirme.', 'Download analytics as CSV.']],
            'analytics.history_days' => ['integer', 'analytics', '30', ['Analitik geçmişi (gün)', 'Analytics history (days)'], ['Görüntülenebilecek geçmiş süre.', 'How far back statistics can be viewed.']],
            'seo.advanced' => ['boolean', 'seo', '0', ['Gelişmiş SEO', 'Advanced SEO'], ['Sosyal paylaşım görseli ve arama motoru görünürlüğü kontrolü.', 'Social share image and search engine visibility control.']],
            'api.access' => ['boolean', 'api', '0', ['API erişimi', 'API access'], ['REST API ve API anahtarları.', 'REST API and API keys.']],
            'api.rate_limit' => ['integer', 'api', '0', ['API istek sınırı (dakika)', 'API rate limit (per minute)'], ['Dakika başına izin verilen API isteği.', 'API requests allowed per minute.']],
            'api.max_tokens' => ['integer', 'api', '0', ['API anahtarı sayısı', 'Number of API keys'], ['Oluşturulabilecek API anahtarı sayısı.', 'How many API keys can exist.']],
            'profile.tracking_pixels' => ['boolean', 'analytics', '0', ['İzleme pikselleri', 'Tracking pixels'], ['Bio sayfasına Google Analytics, Google Ads, Google Tag Manager, Meta ve TikTok pikselleri ekleme (ziyaretçi izniyle yüklenir).', 'Add Google Analytics, Google Ads, Google Tag Manager, Meta and TikTok pixels to a bio page (loaded with visitor consent).']],
            'shortlinks.max' => ['integer', 'content', '10', ['Kısa bağlantı sayısı', 'Short links'], ['Oluşturulabilecek kısa bağlantı sayısı.', 'How many short links can be created.']],
        ];
    }

    public function run(): void
    {
        $order = 0;

        foreach (self::catalog() as $key => [$type, $group, $default, $name, $description]) {
            FeatureDefinition::query()->firstOrCreate(['key' => $key], [
                'type' => $type,
                'group' => $group,
                'default_value' => $default,
                'name' => ['tr' => $name[0], 'en' => $name[1]],
                'description' => ['tr' => $description[0], 'en' => $description[1]],
                'platform_enabled' => true,
                'platform_cap' => null,
                'sort_order' => $order++,
            ]);
        }
    }
}
