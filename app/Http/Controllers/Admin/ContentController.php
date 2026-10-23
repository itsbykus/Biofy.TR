<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Page;
use App\Services\AuditLogger;
use App\Services\Mail\EmailTemplates;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CMS pages, header/footer menus and e-mail templates. */
class ContentController extends Controller
{
    private const SYSTEM_PATHS = ['about', 'help', 'privacy', 'terms', 'cookies', 'kvkk', 'contact'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Settings $settings,
    ) {
    }

    public function pages(): View
    {
        return view('admin.content.pages', ['pages' => Page::query()->orderByDesc('is_system')->orderBy('slug')->get()]);
    }

    public function createPage(): View
    {
        return view('admin.content.page-form', ['page' => new Page(['is_published' => true])]);
    }

    public function storePage(Request $request): RedirectResponse
    {
        $page = Page::query()->create($this->validatedPage($request));
        $this->audit->log('page.created', $page, ['slug' => $page->slug]);

        return redirect()->route('admin.pages.edit', $page)->with('success', __('admin.saved'));
    }

    public function editPage(Page $page): View
    {
        return view('admin.content.page-form', ['page' => $page]);
    }

    public function updatePage(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validatedPage($request, $page));
        $this->audit->log('page.updated', $page, ['slug' => $page->slug]);

        return back()->with('success', __('admin.saved'));
    }

    public function destroyPage(Page $page): RedirectResponse
    {
        if ($page->is_system) {
            return back()->with('error', __('admin.pages.system_cannot_delete'));
        }

        $this->audit->log('page.deleted', $page, ['slug' => $page->slug]);
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', __('admin.deleted'));
    }

    public function menus(): View
    {
        return view('admin.content.menus', [
            'header' => $this->settings->get('site.header_menu'),
            'footer' => $this->settings->get('site.footer_menu'),
        ]);
    }

    public function updateMenus(Request $request): RedirectResponse
    {
        $request->validate([
            'header' => ['nullable', 'array', 'max:12'],
            'footer' => ['nullable', 'array', 'max:20'],
            '*.*.label_tr' => ['nullable', 'string', 'max:60'],
            '*.*.label_en' => ['nullable', 'string', 'max:60'],
            // Internal paths ("/pricing") or absolute https URLs only.
            '*.*.url' => ['nullable', 'string', 'max:500', 'regex:#^(/([^/].*)?|https://\S+)$#'],
        ]);

        foreach (['header' => 'site.header_menu', 'footer' => 'site.footer_menu'] as $input => $key) {
            $items = collect($request->input($input, []))
                ->filter(fn ($row) => ! empty($row['url']) && (! empty($row['label_tr']) || ! empty($row['label_en'])))
                ->map(fn ($row) => ['label' => ['tr' => (string) ($row['label_tr'] ?? ''), 'en' => (string) ($row['label_en'] ?? '')], 'url' => $row['url']])
                ->values()->all();

            $this->settings->set($key, $items, $request->user()->id);
        }

        $this->audit->log('menus.updated');

        return back()->with('success', __('admin.saved'));
    }

    public function templates(): View
    {
        return view('admin.content.templates', [
            'keys' => EmailTemplates::KEYS,
            'templates' => EmailTemplate::query()->get()->groupBy('key'),
        ]);
    }

    public function editTemplate(string $key, string $locale): View
    {
        abort_unless(in_array($key, EmailTemplates::KEYS, true) && in_array($locale, config('biofy.locales'), true), 404);

        return view('admin.content.template-form', [
            'key' => $key,
            'locale' => $locale,
            'template' => EmailTemplate::query()->firstOrNew(['key' => $key, 'locale' => $locale], [
                'subject' => __('emails.'.$key.'.subject', [], $locale),
                'body' => __('emails.'.$key.'.body', [], $locale),
            ]),
            'placeholders' => EmailTemplates::placeholders($key),
        ]);
    }

    public function updateTemplate(Request $request, string $key, string $locale): RedirectResponse
    {
        abort_unless(in_array($key, EmailTemplates::KEYS, true) && in_array($locale, config('biofy.locales'), true), 404);
        $data = $request->validate(['subject' => ['required', 'string', 'max:200'], 'body' => ['required', 'string', 'max:5000']]);

        if ($request->boolean('reset')) {
            $data = ['subject' => __('emails.'.$key.'.subject', [], $locale), 'body' => __('emails.'.$key.'.body', [], $locale)];
        }

        EmailTemplate::query()->updateOrCreate(['key' => $key, 'locale' => $locale], $data + ['is_customized' => ! $request->boolean('reset')]);
        $this->audit->log('email_template.updated', null, ['key' => $key, 'locale' => $locale, 'reset' => $request->boolean('reset')]);

        return back()->with('success', __('admin.saved'));
    }

    private function validatedPage(Request $request, ?Page $page = null): array
    {
        $request->merge(['slug' => strtolower(trim((string) $request->input('slug')))]);
        $data = $request->validate([
            // System pages keep their slug (their URLs are fixed routes).
            'slug' => $page?->is_system ? ['nullable'] : ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'title.tr' => ['required', 'string', 'max:150'],
            'title.en' => ['nullable', 'string', 'max:150'],
            'body.tr' => ['nullable', 'string', 'max:60000'],
            'body.en' => ['nullable', 'string', 'max:60000'],
            'meta_description.tr' => ['nullable', 'string', 'max:300'],
            'meta_description.en' => ['nullable', 'string', 'max:300'],
        ]);

        return [
            'slug' => $page?->is_system ? $page->slug : $data['slug'],
            'title' => array_filter($data['title']),
            'body' => array_filter($data['body'] ?? []),
            'meta_description' => array_filter($data['meta_description'] ?? []),
            'is_published' => $request->boolean('is_published'),
        ];
    }

    public static function pagePath(Page $page): string
    {
        return in_array($page->slug, self::SYSTEM_PATHS, true) ? '/'.$page->slug : '/page/'.$page->slug;
    }
}
