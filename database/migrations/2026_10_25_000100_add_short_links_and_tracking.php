<?php

use App\Support\FeatureCatalogUpgrades;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1.1.0: short links, per-page tracking pixels and the two plan features behind them.
 * Feature rows are added here (not only in the seeders) so existing installs get them on update.
 */
return new class extends Migration
{
    private const MARKETING = [
        'basic' => [
            ['tr' => ['1 bio sayfası', 'Profil resmi ve açıklama', '15 bağlantıya kadar', 'Hazır temalar', 'Temel istatistikler (30 gün)', 'Temel SEO alanları', 'QR kod'],
                'en' => ['1 bio page', 'Profile picture and bio', 'Up to 15 links', 'Ready-made themes', 'Basic statistics (30 days)', 'Basic SEO fields', 'QR code']],
            ['tr' => ['1 bio sayfası', 'Profil resmi ve açıklama', '15 bağlantıya kadar', 'Hazır temalar', 'Temel istatistikler (30 gün)', 'Temel SEO alanları', '10 kısa bağlantı ve QR kod oluşturucu'],
                'en' => ['1 bio page', 'Profile picture and bio', 'Up to 15 links', 'Ready-made themes', 'Basic statistics (30 days)', 'Basic SEO fields', '10 short links and a QR code generator']],
        ],
        'pro' => [
            ['tr' => ['Basic paketindeki her şey', '5 bio sayfası', 'Premium temalar ve gelişmiş özelleştirme', 'Video, müzik, galeri, ürün ve daha fazla blok', 'Zamanlanmış bağlantılar', 'Detaylı analitik ve CSV dışa aktarma', 'Özel alan adı', 'Gelişmiş SEO ve API erişimi', 'Biofy markasını gizleme'],
                'en' => ['Everything in Basic', '5 bio pages', 'Premium themes and advanced customization', 'Video, music, gallery, product and more blocks', 'Scheduled links', 'Detailed analytics and CSV export', 'Custom domain', 'Advanced SEO and API access', 'Remove Biofy branding']],
            ['tr' => ['Basic paketindeki her şey', '5 bio sayfası', 'Premium temalar ve gelişmiş özelleştirme', 'Video, müzik, galeri, ürün ve daha fazla blok', 'Zamanlanmış bağlantılar', 'Detaylı analitik ve CSV dışa aktarma', 'Özel alan adı', 'Gelişmiş SEO ve API erişimi', 'Google, Meta ve TikTok pikselleri', '200 kısa bağlantı', 'Biofy markasını gizleme'],
                'en' => ['Everything in Basic', '5 bio pages', 'Premium themes and advanced customization', 'Video, music, gallery, product and more blocks', 'Scheduled links', 'Detailed analytics and CSV export', 'Custom domain', 'Advanced SEO and API access', 'Google, Meta and TikTok pixels', '200 short links', 'Remove Biofy branding']],
        ],
    ];

    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Lowercase [a-z0-9_-]; served at /s/{code}.
            $table->string('code', 40)->unique();
            $table->text('target_url');
            $table->string('title', 120)->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            // Staff can switch off a link that breaks the rules without deleting it.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            // {"ga4": "G-…", "gtm": "GTM-…", "google_ads": "AW-…", "meta": "123…", "tiktok": "C…"}
            $table->json('tracking')->nullable()->after('seo_noindex');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            // Search engine / social overrides; empty values fall back to the title, excerpt and cover.
            $table->json('seo_title')->nullable()->after('meta_description');
            $table->foreignId('seo_image_media_id')->nullable()->after('cover_media_id')->constrained('media_files')->nullOnDelete();
            $table->boolean('seo_noindex')->default(false)->after('seo_image_media_id');
            $table->string('canonical_url', 2048)->nullable()->after('seo_noindex');
        });

        // Plan features behind them (shared with the restore path, see FeatureCatalogUpgrades).
        FeatureCatalogUpgrades::apply();

        // Plan cards on the pricing page: updated only where the operator kept the original texts.
        foreach (self::MARKETING as $packageKey => [$old, $new]) {
            $package = DB::table('packages')->where('key', $packageKey)->first(['id', 'marketing_features']);

            if ($package && json_decode((string) $package->marketing_features, true) === $old) {
                DB::table('packages')->where('id', $package->id)->update(['marketing_features' => json_encode($new, JSON_UNESCAPED_UNICODE)]);
            }
        }

        // Cached entitlements must see the new features.
        app(\App\Services\Entitlements::class)->flushAll();
    }

    public function down(): void
    {
        DB::table('package_features')->whereIn('feature_key', array_keys(FeatureCatalogUpgrades::FEATURES))->delete();
        DB::table('feature_definitions')->whereIn('key', array_keys(FeatureCatalogUpgrades::FEATURES))->delete();

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('tracking');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seo_image_media_id');
            $table->dropColumn(['seo_title', 'seo_noindex', 'canonical_url']);
        });

        Schema::dropIfExists('short_links');
    }
};
