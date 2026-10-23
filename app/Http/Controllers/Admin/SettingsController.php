<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\Package;
use App\Notifications\GenericTemplatedNotification;
use App\Services\AuditLogger;
use App\Services\Mail\MailConfigurator;
use App\Services\MediaService;
use App\Services\Settings;
use App\Support\SettingDefinitions;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

/**
 * One generic screen per settings group. Every field shown here is read by the
 * application at runtime (no decorative options). Secret values are write-only.
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly Settings $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    public static function groups(): array
    {
        return [
            'general' => ['site.name', 'site.tagline', 'site.description', 'site.support_email', 'site.default_locale', 'site.timezone', 'site.default_country', 'site.social_links'],
            'brand' => ['site.logo_media_id', 'site.logo_height', 'site.favicon_media_id', 'site.primary_color', 'site.secondary_color', 'site.accent_color', 'profiles.default_theme'],
            'home' => ['home.hero_style', 'home.badge', 'home.hero_title', 'home.hero_subtitle', 'home.cta_label', 'home.secondary_cta_label', 'home.secondary_cta_url',
                'home.show_features', 'home.features_title', 'home.features_text', 'home.show_steps', 'home.show_themes', 'home.show_pricing',
                'home.cta_title', 'home.cta_text', 'pricing.premium_message'],
            'footer' => ['footer.style', 'footer.logo_media_id', 'footer.logo_height', 'footer.show_brand_text', 'footer.brand_text', 'site.footer_text',
                'footer.show_social', 'footer.product_title', 'footer.company_title', 'footer.copyright'],
            'seo' => ['seo.default_title', 'seo.default_description', 'seo.og_image_media_id', 'seo.allow_indexing'],
            'auth' => ['auth.login_title', 'auth.login_subtitle', 'auth.register_title', 'auth.register_subtitle', 'registration.enabled', 'registration.require_email_verification', 'registration.default_package', 'profiles.slug_redirect_days'],
            'announcement' => ['announcement.enabled', 'announcement.text', 'announcement.url', 'announcement.style', 'maintenance.enabled', 'maintenance.message', 'maintenance.keep_profiles_online'],
            'mail' => ['mail.mailer', 'mail.host', 'mail.port', 'mail.encryption', 'mail.username', 'mail.password', 'mail.from_address', 'mail.from_name'],
            'oauth' => ['oauth.google.enabled', 'oauth.google.client_id', 'oauth.google.client_secret'],
            'platform' => ['api.enabled', 'domains.enabled', 'domains.cname_target', 'domains.a_record_ip', 'domains.require_https', 'domains.instructions'],
            'privacy' => ['privacy.cookie_notice', 'analytics.retention_days', 'analytics.trust_cf_country', 'privacy.security_log_retention_days', 'privacy.account_deletion_grace_days'],
            'security' => ['security.session_lifetime', 'security.password_min_length', 'security.login_max_attempts', 'security.admin_require_2fa', 'security.hsts'],
            'backups' => ['backups.encrypt', 'backups.include_media', 'backups.keep_last', 'backups.schedule'],
        ];
    }

    /** Select options and validation for fields that need more than their type. */
    private function fieldMeta(): array
    {
        return [
            'site.default_locale' => ['options' => ['tr' => 'Türkçe', 'en' => 'English'], 'rules' => ['in:tr,en']],
            'site.timezone' => ['options' => array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers()), 'rules' => ['timezone:all']],
            'site.default_country' => ['rules' => ['regex:/^[A-Z]{2}$/']],
            'site.support_email' => ['rules' => ['nullable', 'email']],
            'profiles.default_theme' => ['options' => collect(app(ThemeRegistry::class)->definitions())->map(fn ($t) => $t['name']['tr'])->all()],
            'registration.default_package' => ['options' => Package::query()->available()->whereNull('custom_for_user_id')->orderBy('sort_order')->get()->mapWithKeys(fn ($p) => [$p->key => $p->translate('name')])->all()],
            'announcement.style' => ['options' => ['brand' => 'Brand', 'info' => 'Info', 'warning' => 'Warning']],
            'announcement.url' => ['rules' => ['nullable', 'regex:#^(/[^/]|https://)#']],
            'site.logo_height' => ['rules' => ['integer', 'between:16,96']],
            'footer.logo_height' => ['rules' => ['integer', 'between:16,96']],
            'footer.style' => ['options' => ['light' => __('admin.settings.options.light'), 'dark' => __('admin.settings.options.dark'), 'brand' => __('admin.settings.options.brand')]],
            'home.hero_style' => ['options' => ['glow' => __('admin.settings.options.glow'), 'dark' => __('admin.settings.options.dark'), 'minimal' => __('admin.settings.options.minimal')]],
            'home.secondary_cta_url' => ['rules' => ['nullable', 'regex:#^(/[^/]|/$|https://)#']],
            'mail.mailer' => ['options' => ['log' => 'Log (no delivery)', 'smtp' => 'SMTP', 'sendmail' => 'Sendmail']],
            'mail.encryption' => ['options' => ['tls' => 'STARTTLS (587)', 'ssl' => 'SSL/TLS (465)', 'none' => 'None']],
            'mail.port' => ['rules' => ['integer', 'between:1,65535']],
            'mail.from_address' => ['rules' => ['nullable', 'email']],
            'domains.a_record_ip' => ['rules' => ['nullable', 'ipv4']],
            'domains.cname_target' => ['rules' => ['nullable', 'regex:/^[a-z0-9.-]+$/i']],
            'backups.schedule' => ['options' => ['off' => 'Off', 'daily' => 'Daily', 'weekly' => 'Weekly']],
            'backups.keep_last' => ['rules' => ['integer', 'between:1,100']],
            'analytics.retention_days' => ['rules' => ['integer', 'between:30,1825']],
            'privacy.security_log_retention_days' => ['rules' => ['integer', 'between:30,1825']],
            'privacy.account_deletion_grace_days' => ['rules' => ['integer', 'between:0,90']],
            'profiles.slug_redirect_days' => ['rules' => ['integer', 'between:0,3650']],
            'security.session_lifetime' => ['rules' => ['integer', 'between:5,43200']],
            'security.password_min_length' => ['rules' => ['integer', 'between:8,128']],
            'security.login_max_attempts' => ['rules' => ['integer', 'between:3,50']],
            'site.primary_color' => ['rules' => ['regex:/^#[0-9A-Fa-f]{6}$/']],
            'site.secondary_color' => ['rules' => ['regex:/^#[0-9A-Fa-f]{6}$/']],
            'site.accent_color' => ['rules' => ['regex:/^#[0-9A-Fa-f]{6}$/']],
        ];
    }

    public function edit(string $group): View
    {
        abort_unless(isset(self::groups()[$group]), 404);
        $meta = $this->fieldMeta();
        $fields = [];

        foreach (self::groups()[$group] as $key) {
            $definition = SettingDefinitions::get($key);
            $fields[$key] = [
                'type' => $definition['type'],
                'value' => $definition['type'] === 'secret' ? null : $this->settings->get($key),
                'has_secret' => $definition['type'] === 'secret' && $this->settings->hasSecret($key),
                'options' => $meta[$key]['options'] ?? null,
                'media' => $definition['type'] === 'media' && $this->settings->get($key) ? MediaFile::query()->find($this->settings->get($key)) : null,
            ];
        }

        return view('admin.settings.edit', [
            'group' => $group,
            'groups' => array_keys(self::groups()),
            'fields' => $fields,
            'googleRedirect' => rtrim((string) config('app.url'), '/').'/auth/google/callback',
        ]);
    }

    public function update(Request $request, string $group, MediaService $media): RedirectResponse
    {
        abort_unless(isset(self::groups()[$group]), 404);
        $meta = $this->fieldMeta();
        $rules = [];

        foreach (self::groups()[$group] as $key) {
            $definition = SettingDefinitions::get($key);
            $input = str_replace('.', '__', $key);
            $rules[$input] = match ($definition['type']) {
                'bool' => ['nullable', 'boolean'],
                'int' => array_merge(['required'], $meta[$key]['rules'] ?? ['integer']),
                'translatable' => ['nullable', 'array'],
                'json' => ['nullable', 'array'],
                'media' => ['nullable', 'file', 'max:2048'],
                'secret' => ['nullable', 'string', 'max:1000'],
                'color' => array_merge(['required'], $meta[$key]['rules'] ?? []),
                default => array_merge(['nullable', 'string', 'max:2000'], isset($meta[$key]['options']) ? ['in:'.implode(',', array_keys($meta[$key]['options']))] : [], $meta[$key]['rules'] ?? []),
            };

            if ($definition['type'] === 'translatable') {
                $rules[$input.'.tr'] = ['nullable', 'string', 'max:2000'];
                $rules[$input.'.en'] = ['nullable', 'string', 'max:2000'];
            }
        }

        $request->validate($rules);
        $changed = [];
        // "Leave as it is" (empty secret field, no new upload). Not false: an unticked checkbox is a real false.
        $keep = new \stdClass;

        foreach (self::groups()[$group] as $key) {
            $definition = SettingDefinitions::get($key);
            $input = str_replace('.', '__', $key);

            $value = match ($definition['type']) {
                'bool' => $request->boolean($input),
                'translatable' => ['tr' => (string) $request->input($input.'.tr', ''), 'en' => (string) $request->input($input.'.en', '')],
                'json' => $this->jsonValue($key, (array) $request->input($input, [])),
                'secret' => $request->boolean($input.'__clear') ? null : ($request->filled($input) ? (string) $request->input($input) : $keep),
                'media' => $request->hasFile($input) ? $media->store($request->file($input), null, $key === 'site.favicon_media_id' ? 'favicon' : ($key === 'seo.og_image_media_id' ? 'seo' : 'brand'))->id : ($request->boolean($input.'__remove') ? null : $keep),
                default => $request->input($input),
            };

            if ($value === $keep) {
                continue;
            }

            $this->settings->set($key, $value, $request->user()->id);
            $changed[] = $key;
        }

        $this->audit->log('settings.updated', null, ['group' => $group, 'keys' => $changed]);
        app(MailConfigurator::class)->apply();
        app(\App\View\Composers\LayoutComposer::class)->forget();

        return back()->with('success', __('admin.saved'));
    }

    public function testMail(Request $request): RedirectResponse
    {
        app(MailConfigurator::class)->apply();

        try {
            $request->user()->notify(new GenericTemplatedNotification('test', ['time' => now()->toDateTimeString()]));
        } catch (Throwable $e) {
            return back()->with('error', __('admin.settings.mail_failed', ['error' => $this->safeError($e)]));
        }

        $this->audit->log('settings.mail_test_sent');

        return back()->with('success', __('admin.settings.mail_sent', ['email' => $request->user()->email]));
    }

    public function checkMail(): RedirectResponse
    {
        app(MailConfigurator::class)->apply();

        if ($this->settings->get('mail.mailer') !== 'smtp') {
            return back()->with('warning', __('admin.settings.mail_not_smtp'));
        }

        try {
            $transport = Mail::mailer('smtp')->getSymfonyTransport();

            if (method_exists($transport, 'start')) {
                $transport->start();
                $transport->stop();
            }
        } catch (Throwable $e) {
            return back()->with('error', __('admin.settings.mail_connection_failed', ['error' => $this->safeError($e)]));
        }

        return back()->with('success', __('admin.settings.mail_connection_ok'));
    }

    private function jsonValue(string $key, array $input): array
    {
        if ($key === 'site.social_links') {
            $links = [];

            foreach ($input as $platform => $url) {
                if (is_string($url) && ($safe = \App\Support\SafeUrl::normalize($url)) && isset(config('biofy.social_platforms')[$platform])) {
                    $links[$platform] = $safe;
                }
            }

            return $links;
        }

        return $input;
    }

    private function safeError(Throwable $e): string
    {
        $message = preg_replace('/(password|pass|secret|auth)\S*/i', '[redacted]', $e->getMessage());

        return mb_substr((string) $message, 0, 300);
    }
}
