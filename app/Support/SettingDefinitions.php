<?php

namespace App\Support;

/**
 * Catalogue of every runtime setting the superadmin panel can change.
 * type: string | text | bool | int | json | translatable | secret | media
 * Settings not listed here can't be written through Settings::set().
 */
final class SettingDefinitions
{
    public static function all(): array
    {
        return [
            // Site & brand
            'site.name' => ['type' => 'string', 'default' => 'Biofy.TR'],
            'site.tagline' => ['type' => 'translatable', 'default' => ['tr' => 'Tüm bağlantıların tek bir sayfada', 'en' => 'All your links on one page']],
            'site.description' => ['type' => 'translatable', 'default' => [
                'tr' => 'Biofy.TR ile sosyal medya hesaplarını, bağlantılarını ve içeriklerini tek, şık ve mobil uyumlu bir sayfada topla.',
                'en' => 'Collect your social accounts, links and content on a single, elegant, mobile-friendly page with Biofy.TR.',
            ]],
            'site.logo_media_id' => ['type' => 'media', 'default' => null],
            'site.logo_height' => ['type' => 'int', 'default' => 32], // px, header logo (image or text mark)
            'site.favicon_media_id' => ['type' => 'media', 'default' => null],
            'site.admin_logo_media_id' => ['type' => 'media', 'default' => null], // admin sidebar (dark); falls back to the site logo
            'site.admin_title' => ['type' => 'translatable', 'default' => ['tr' => '', 'en' => '']],
            'site.primary_color' => ['type' => 'color', 'default' => '#FFCF00'],
            'site.secondary_color' => ['type' => 'color', 'default' => '#111111'],
            'site.accent_color' => ['type' => 'color', 'default' => '#2563EB'],
            'site.default_locale' => ['type' => 'string', 'default' => 'tr'],
            'site.timezone' => ['type' => 'string', 'default' => 'Europe/Istanbul'],
            'site.default_country' => ['type' => 'string', 'default' => 'TR'],
            'site.support_email' => ['type' => 'string', 'default' => ''],
            'site.social_links' => ['type' => 'json', 'default' => []],
            'site.header_menu' => ['type' => 'json', 'default' => [
                ['label' => ['tr' => 'Özellikler', 'en' => 'Features'], 'url' => '/features'],
                ['label' => ['tr' => 'Temalar', 'en' => 'Themes'], 'url' => '/themes'],
                ['label' => ['tr' => 'Fiyatlandırma', 'en' => 'Pricing'], 'url' => '/pricing'],
                ['label' => ['tr' => 'Blog', 'en' => 'Blog'], 'url' => '/blog'],
                ['label' => ['tr' => 'Yardım', 'en' => 'Help'], 'url' => '/help'],
            ]],
            'site.footer_menu' => ['type' => 'json', 'default' => [
                ['label' => ['tr' => 'Hakkımızda', 'en' => 'About'], 'url' => '/about'],
                ['label' => ['tr' => 'İletişim', 'en' => 'Contact'], 'url' => '/contact'],
                ['label' => ['tr' => 'Gizlilik Politikası', 'en' => 'Privacy Policy'], 'url' => '/privacy'],
                ['label' => ['tr' => 'Kullanım Koşulları', 'en' => 'Terms of Use'], 'url' => '/terms'],
                ['label' => ['tr' => 'Çerez Politikası', 'en' => 'Cookie Policy'], 'url' => '/cookies'],
            ]],
            'site.footer_text' => ['type' => 'translatable', 'default' => ['tr' => '', 'en' => '']],

            // Footer (empty texts fall back to the site name / tagline)
            'footer.style' => ['type' => 'string', 'default' => 'light'],
            'footer.logo_media_id' => ['type' => 'media', 'default' => null],
            'footer.logo_height' => ['type' => 'int', 'default' => 28],
            'footer.show_brand_text' => ['type' => 'bool', 'default' => true],
            'footer.brand_text' => ['type' => 'translatable', 'default' => ['tr' => '', 'en' => '']],
            'footer.show_social' => ['type' => 'bool', 'default' => true],
            'footer.product_title' => ['type' => 'translatable', 'default' => ['tr' => 'Ürün', 'en' => 'Product']],
            'footer.company_title' => ['type' => 'translatable', 'default' => ['tr' => 'Kurumsal', 'en' => 'Company']],
            'footer.copyright' => ['type' => 'translatable', 'default' => ['tr' => '© {year} {site}. Tüm hakları saklıdır.', 'en' => '© {year} {site}. All rights reserved.']],

            // Home page (landing) CMS
            'home.hero_style' => ['type' => 'string', 'default' => 'glow'],
            'home.hero_image_media_id' => ['type' => 'media', 'default' => null], // replaces the built-in product mock-up
            'home.badge' => ['type' => 'translatable', 'default' => ['tr' => '', 'en' => '']],
            'home.hero_title' => ['type' => 'translatable', 'default' => ['tr' => 'Tek bağlantı. Tüm dünyan.', 'en' => 'One link. Your whole world.']],
            'home.hero_subtitle' => ['type' => 'translatable', 'default' => [
                'tr' => 'Sosyal medya hesaplarını, içeriklerini ve işini tek bir şık sayfada topla. Dakikalar içinde yayınla.',
                'en' => 'Bring your social accounts, content and business together on one elegant page. Publish in minutes.',
            ]],
            'home.cta_label' => ['type' => 'translatable', 'default' => ['tr' => 'Ücretsiz başla', 'en' => 'Start for free']],
            'home.secondary_cta_label' => ['type' => 'translatable', 'default' => ['tr' => 'Temaları keşfet', 'en' => 'Explore themes']],
            'home.secondary_cta_url' => ['type' => 'string', 'default' => '/themes'],
            'home.show_features' => ['type' => 'bool', 'default' => true],
            'home.features_title' => ['type' => 'translatable', 'default' => ['tr' => 'Bir bağlantıdan çok daha fazlası', 'en' => 'Much more than a link']],
            'home.features_text' => ['type' => 'translatable', 'default' => [
                'tr' => 'Sayfanı tasarla, içeriklerini paylaş, ziyaretçilerini anla — teknik bilgi gerekmeden.',
                'en' => 'Design your page, share your content and understand your visitors — no technical skills needed.',
            ]],
            'home.show_steps' => ['type' => 'bool', 'default' => true],
            'home.show_themes' => ['type' => 'bool', 'default' => true],
            'home.show_pricing' => ['type' => 'bool', 'default' => true],
            'home.cta_title' => ['type' => 'translatable', 'default' => ['tr' => 'Sayfanı bugün oluştur.', 'en' => 'Create your page today.']],
            'home.cta_text' => ['type' => 'translatable', 'default' => [
                'tr' => 'Ücretsiz hesabını aç, temanı seç ve bağlantını dakikalar içinde paylaş.',
                'en' => 'Open a free account, pick a theme and share your link in minutes.',
            ]],

            // "Made with" badge at the bottom of bio pages (hidden on plans with profile.remove_branding)
            'branding.badge_text' => ['type' => 'translatable', 'default' => ['tr' => '{site} ile oluşturuldu', 'en' => 'Made with {site}']],
            'branding.badge_logo_media_id' => ['type' => 'media', 'default' => null],
            'branding.badge_url' => ['type' => 'string', 'default' => ''],

            // Blog
            'blog.title' => ['type' => 'translatable', 'default' => ['tr' => 'Blog', 'en' => 'Blog']],
            'blog.description' => ['type' => 'translatable', 'default' => [
                'tr' => 'Link-in-bio ipuçları, ürün haberleri ve rehberler.',
                'en' => 'Link-in-bio tips, product news and guides.',
            ]],

            // SEO
            'seo.default_title' => ['type' => 'translatable', 'default' => ['tr' => 'Biofy.TR — Link-in-bio sayfanı oluştur', 'en' => 'Biofy.TR — Create your link-in-bio page']],
            'seo.default_description' => ['type' => 'translatable', 'default' => [
                'tr' => 'Tüm bağlantıların, sosyal medya hesapların ve içeriklerin için tek, hızlı ve mobil uyumlu bio sayfası.',
                'en' => 'One fast, mobile-friendly bio page for all your links, social accounts and content.',
            ]],
            'seo.og_image_media_id' => ['type' => 'media', 'default' => null],
            'seo.allow_indexing' => ['type' => 'bool', 'default' => true],

            // Auth screens
            'auth.login_title' => ['type' => 'translatable', 'default' => ['tr' => 'Tekrar hoş geldin', 'en' => 'Welcome back']],
            'auth.login_subtitle' => ['type' => 'translatable', 'default' => ['tr' => 'Hesabına giriş yap ve sayfanı yönet.', 'en' => 'Sign in to manage your page.']],
            'auth.register_title' => ['type' => 'translatable', 'default' => ['tr' => 'Sayfanı oluştur', 'en' => 'Create your page']],
            'auth.register_subtitle' => ['type' => 'translatable', 'default' => ['tr' => 'Birkaç saniyede ücretsiz hesap aç.', 'en' => 'Open a free account in seconds.']],

            // Announcement & maintenance
            'announcement.enabled' => ['type' => 'bool', 'default' => false],
            'announcement.text' => ['type' => 'translatable', 'default' => ['tr' => '', 'en' => '']],
            'announcement.url' => ['type' => 'string', 'default' => ''],
            'announcement.style' => ['type' => 'string', 'default' => 'brand'],
            'maintenance.enabled' => ['type' => 'bool', 'default' => false],
            'maintenance.message' => ['type' => 'translatable', 'default' => [
                'tr' => 'Kısa bir bakım çalışması yapıyoruz. Lütfen biraz sonra tekrar deneyin.',
                'en' => 'We are performing short maintenance. Please try again shortly.',
            ]],
            'maintenance.keep_profiles_online' => ['type' => 'bool', 'default' => true],

            // Registration & profiles
            'registration.enabled' => ['type' => 'bool', 'default' => true],
            'registration.require_email_verification' => ['type' => 'bool', 'default' => true],
            'registration.default_package' => ['type' => 'string', 'default' => 'basic'],
            'profiles.default_theme' => ['type' => 'string', 'default' => 'minimal-light'],
            'profiles.slug_redirect_days' => ['type' => 'int', 'default' => 90],

            // Mail
            'mail.mailer' => ['type' => 'string', 'default' => 'log'],
            'mail.host' => ['type' => 'string', 'default' => ''],
            'mail.port' => ['type' => 'int', 'default' => 587],
            'mail.username' => ['type' => 'string', 'default' => ''],
            'mail.password' => ['type' => 'secret', 'default' => null],
            'mail.encryption' => ['type' => 'string', 'default' => 'tls'],
            'mail.from_address' => ['type' => 'string', 'default' => ''],
            'mail.from_name' => ['type' => 'string', 'default' => 'Biofy.TR'],

            // Google OAuth
            'oauth.google.enabled' => ['type' => 'bool', 'default' => false],
            'oauth.google.client_id' => ['type' => 'string', 'default' => ''],
            'oauth.google.client_secret' => ['type' => 'secret', 'default' => null],

            // Platform switches
            'api.enabled' => ['type' => 'bool', 'default' => true],
            'domains.enabled' => ['type' => 'bool', 'default' => true],
            'domains.cname_target' => ['type' => 'string', 'default' => ''],
            'domains.a_record_ip' => ['type' => 'string', 'default' => ''],
            'domains.require_https' => ['type' => 'bool', 'default' => true],
            'domains.instructions' => ['type' => 'translatable', 'default' => ['tr' => '', 'en' => '']],

            // Analytics & privacy
            'analytics.retention_days' => ['type' => 'int', 'default' => 365],
            'analytics.trust_cf_country' => ['type' => 'bool', 'default' => false],
            'privacy.security_log_retention_days' => ['type' => 'int', 'default' => 180],
            'privacy.account_deletion_grace_days' => ['type' => 'int', 'default' => 14],
            'privacy.cookie_notice' => ['type' => 'bool', 'default' => true],

            // Backups
            'backups.encrypt' => ['type' => 'bool', 'default' => true],
            'backups.keep_last' => ['type' => 'int', 'default' => 10],
            'backups.schedule' => ['type' => 'string', 'default' => 'off'],
            'backups.include_media' => ['type' => 'bool', 'default' => true],

            // Security
            'security.session_lifetime' => ['type' => 'int', 'default' => 120],
            'security.confirm_timeout_minutes' => ['type' => 'int', 'default' => 30], // re-authentication stays valid this long
            'security.password_min_length' => ['type' => 'int', 'default' => 10],
            'security.login_max_attempts' => ['type' => 'int', 'default' => 5],
            'security.admin_require_2fa' => ['type' => 'bool', 'default' => false],
            'security.hsts' => ['type' => 'bool', 'default' => false],

            // Pricing page
            'pricing.premium_message' => ['type' => 'translatable', 'default' => [
                'tr' => 'Daha fazlasına mı ihtiyacınız var? Bizimle irtibata geçiniz.',
                'en' => 'Need more? Get in touch with us.',
            ]],

            // Internal (not shown as editable fields)
            'cron.token' => ['type' => 'secret', 'default' => null, 'internal' => true],
            'cron.last_run_at' => ['type' => 'string', 'default' => null, 'internal' => true],
            'system.installed_version' => ['type' => 'string', 'default' => null, 'internal' => true],
            'system.installed_at' => ['type' => 'string', 'default' => null, 'internal' => true],
            'system.features_version' => ['type' => 'int', 'default' => 1, 'internal' => true],
            'system.last_backup_at' => ['type' => 'string', 'default' => null, 'internal' => true],
        ];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
