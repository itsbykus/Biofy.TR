<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageFeature;
use Illuminate\Database\Seeder;

/**
 * The three starting packages. Prices are editable product information only:
 * there is no payment processing in v1 (see docs/ROADMAP.md).
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $basic = $this->package('basic', [
            'name' => ['tr' => 'Basic', 'en' => 'Basic'],
            'description' => ['tr' => 'Bio sayfanı oluşturup yayınlamak için gereken her şey.', 'en' => 'Everything you need to create and publish your bio page.'],
            'marketing_features' => [
                'tr' => ['1 bio sayfası', 'Profil resmi ve açıklama', '15 bağlantıya kadar', 'Hazır temalar', 'Temel istatistikler (30 gün)', 'Temel SEO alanları', 'QR kod'],
                'en' => ['1 bio page', 'Profile picture and bio', 'Up to 15 links', 'Ready-made themes', 'Basic statistics (30 days)', 'Basic SEO fields', 'QR code'],
            ],
            'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'TRY',
            'is_public' => true, 'is_default' => true, 'sort_order' => 1,
        ]);

        $pro = $this->package('pro', [
            'name' => ['tr' => 'Pro', 'en' => 'Pro'],
            'description' => ['tr' => 'Gelişmiş kişiselleştirme, içerik blokları ve detaylı analitik.', 'en' => 'Advanced customization, content blocks and detailed analytics.'],
            'marketing_features' => [
                'tr' => ['Basic paketindeki her şey', '5 bio sayfası', 'Premium temalar ve gelişmiş özelleştirme', 'Video, müzik, galeri, ürün ve daha fazla blok', 'Zamanlanmış bağlantılar', 'Detaylı analitik ve CSV dışa aktarma', 'Özel alan adı', 'Gelişmiş SEO ve API erişimi', 'Biofy markasını gizleme'],
                'en' => ['Everything in Basic', '5 bio pages', 'Premium themes and advanced customization', 'Video, music, gallery, product and more blocks', 'Scheduled links', 'Detailed analytics and CSV export', 'Custom domain', 'Advanced SEO and API access', 'Remove Biofy branding'],
            ],
            'price_monthly' => 99, 'price_yearly' => 990, 'currency' => 'TRY',
            'is_public' => true, 'is_highlighted' => true, 'sort_order' => 2,
        ]);

        $premium = $this->package('premium', [
            'name' => ['tr' => 'Premium', 'en' => 'Premium'],
            'description' => ['tr' => 'Kurumlar ve ajanslar için özel anlaşmalı paket.', 'en' => 'Custom-agreement package for organisations and agencies.'],
            'is_contact_only' => true,
            'contact_message' => ['tr' => 'Daha fazlasına mı ihtiyacınız var? Bizimle irtibata geçiniz.', 'en' => 'Need more? Get in touch with us.'],
            'contact_url' => '/contact?package=premium',
            'currency' => 'TRY', 'is_public' => true, 'sort_order' => 3,
        ]);

        $matrix = [
            //                                   basic  pro    premium
            'profile.max_pages' => ['1', '5', '-1'],
            'profile.max_links' => ['15', '100', '-1'],
            'profile.max_social_links' => ['6', '25', '-1'],
            'profile.max_blocks' => ['30', '200', '-1'],
            'profile.slug_redirects' => ['0', '1', '1'],
            'profile.custom_theme' => ['0', '1', '1'],
            'profile.advanced_customization' => ['0', '1', '1'],
            'profile.remove_branding' => ['0', '1', '1'],
            'profile.advanced_blocks' => ['0', '1', '1'],
            'profile.scheduled_links' => ['0', '1', '1'],
            'profile.custom_domain' => ['0', '1', '1'],
            'domain.max_domains' => ['0', '1', '10'],
            'storage.quota_mb' => ['25', '500', '5000'],
            'analytics.detailed' => ['0', '1', '1'],
            'analytics.export' => ['0', '1', '1'],
            'analytics.history_days' => ['30', '365', '-1'],
            'seo.advanced' => ['0', '1', '1'],
            'api.access' => ['0', '1', '1'],
            'api.rate_limit' => ['0', '60', '600'],
            'api.max_tokens' => ['0', '5', '20'],
        ];

        foreach ($matrix as $key => [$b, $p, $pr]) {
            foreach ([[$basic, $b], [$pro, $p], [$premium, $pr]] as [$package, $value]) {
                if ($package->wasRecentlyCreated) {
                    PackageFeature::query()->firstOrCreate(['package_id' => $package->id, 'feature_key' => $key], ['value' => $value]);
                }
            }
        }
    }

    private function package(string $key, array $attributes): Package
    {
        return Package::query()->firstOrCreate(['key' => $key], $attributes);
    }
}
