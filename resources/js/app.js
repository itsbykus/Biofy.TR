// Biofy.TR — progressive enhancements for the site, dashboard and admin panel.
// Every feature degrades gracefully: forms and links work without JavaScript.

import './svg-upload';
import './tracking';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

export function toast(message, type = 'info') {
    let region = $('#toast-region');

    if (!region) {
        region = document.createElement('div');
        region.id = 'toast-region';
        region.className = 'pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4';
        region.setAttribute('role', 'status');
        region.setAttribute('aria-live', 'polite');
        document.body.append(region);
    }

    const el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' !bg-red-700' : '');
    el.textContent = message;
    region.append(el);
    setTimeout(() => el.remove(), 4000);
}

// Confirmation before destructive actions: <form data-confirm="..."> or <button data-confirm="...">
document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = event.submitter?.dataset.confirm || (form instanceof HTMLFormElement ? form.dataset.confirm : null);

    if (message && !window.confirm(message)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);

// Copy to clipboard: <button data-copy="text">
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');

    if (!button) {
        return;
    }

    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        toast(button.dataset.copied || 'OK');
    } catch {
        window.prompt('', button.dataset.copy);
    }
});

// Mobile navigation toggle: <button data-nav-toggle="#id">
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-nav-toggle]');

    if (!button) {
        return;
    }

    const target = $(button.dataset.navToggle);

    // Sidebars use the `hidden` utility class (shown from lg: up); small menus use the attribute.
    if (target.classList.contains('hidden') || target.dataset.navClass) {
        target.dataset.navClass = '1';
        const open = target.classList.toggle('hidden') === false;
        $$(`[data-nav-toggle="${button.dataset.navToggle}"]`).forEach((b) => b.setAttribute('aria-expanded', String(open)));

        return;
    }

    const open = target.hasAttribute('hidden');
    target.toggleAttribute('hidden', !open);
    button.setAttribute('aria-expanded', String(open));
});

// Password visibility: <button data-toggle-password="#input">
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-toggle-password]');

    if (!button) {
        return;
    }

    const input = $(button.dataset.togglePassword);
    input.type = input.type === 'password' ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(input.type === 'text'));
});

// Dialogs: <button data-dialog-open="#id">, <button data-dialog-close>
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');

    if (opener) {
        $(opener.dataset.dialogOpen)?.showModal();

        return;
    }

    const closer = event.target.closest('[data-dialog-close]');

    if (closer) {
        closer.closest('dialog')?.close();
    }
});

// Auto-submit filters: <select data-autosubmit>
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-autosubmit]')) {
        event.target.form?.requestSubmit();
    }
});

// Colour inputs kept in sync with their hex text field: <input type=color data-color-sync="#text">
document.addEventListener('input', (event) => {
    const el = event.target;

    if (el.matches('[data-color-sync]')) {
        const other = $(el.dataset.colorSync);

        if (other && /^#[0-9a-fA-F]{6}$/.test(el.value)) {
            other.value = el.value.toUpperCase();
        }
    }
});

// Image inputs uploaded with their form: preview and file name before saving.
// <div data-file-preview data-pending-text="..."> <img data-file-preview-img> <input type=file data-file-preview-input> <p data-file-preview-name>
document.addEventListener('change', (event) => {
    const input = event.target;

    if (!input.matches('[data-file-preview-input]')) {
        return;
    }

    const root = input.closest('[data-file-preview]');
    const name = $('[data-file-preview-name]', root);
    const file = input.files?.[0];
    name.hidden = !file;

    if (!file) {
        return;
    }

    name.textContent = `${file.name} — ${root.dataset.pendingText}`;
    const reader = new FileReader();
    reader.addEventListener('load', () => {
        const img = $('[data-file-preview-img]', root);
        img.src = reader.result;
        img.hidden = false;
        $('[data-file-preview-empty]', root)?.setAttribute('hidden', '');
    });
    reader.readAsDataURL(file);
});

// Repeatable rows: <div data-repeater> <template data-repeater-template> ... </template> <div data-repeater-list> ... <button data-repeater-add>
export function initRepeaters(root = document) {
    $$('[data-repeater]', root).forEach((repeater) => {
        if (repeater.dataset.bound) {
            return;
        }

        repeater.dataset.bound = '1';
        const list = $('[data-repeater-list]', repeater);
        const template = $('template[data-repeater-template]', repeater);
        const max = parseInt(repeater.dataset.max || '50', 10);

        const reindex = () => {
            $$('[data-repeater-item]', list).forEach((item, index) => {
                $$('[name]', item).forEach((field) => {
                    field.name = field.name.replace(/\[(\d+|__i__)\]/, `[${index}]`);
                });
            });
        };

        repeater.addEventListener('click', (event) => {
            if (event.target.closest('[data-repeater-add]')) {
                event.preventDefault();

                if ($$('[data-repeater-item]', list).length >= max) {
                    return;
                }

                list.append(template.content.cloneNode(true));
                reindex();
                document.dispatchEvent(new CustomEvent('biofy:repeater-added', { detail: { repeater } }));
            }

            const remove = event.target.closest('[data-repeater-remove]');

            if (remove) {
                event.preventDefault();
                remove.closest('[data-repeater-item]').remove();
                reindex();
            }
        });
    });
}

initRepeaters();

// Cookie notice (only strictly-necessary cookies are used, so this is information, not consent).
const notice = $('#cookie-notice');

if (notice) {
    try {
        if (!localStorage.getItem('biofy.cookie-notice')) {
            notice.hidden = false;
        }
    } catch {
        notice.hidden = false;
    }

    notice.querySelector('button')?.addEventListener('click', () => {
        notice.hidden = true;

        try {
            localStorage.setItem('biofy.cookie-notice', '1');
        } catch {
            // storage unavailable (private mode) — nothing to remember
        }
    });
}

// Blog editor: live search result preview (SEO title -> title, description -> excerpt, slug).
const seoPreview = $('[data-seo-preview]');

if (seoPreview) {
    const form = seoPreview.closest('form');
    const value = (name) => (form.elements.namedItem(name)?.value || '').trim();
    const update = () => {
        $('[data-seo-title]', seoPreview).textContent = value('seo_title[tr]') || value('title[tr]') || '…';
        $('[data-seo-desc]', seoPreview).textContent = (value('meta_description[tr]') || value('excerpt[tr]')).slice(0, 160);
        const slug = value('slug') || value('title[tr]').toLocaleLowerCase('tr').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ı/g, 'i').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        $('[data-seo-url]', seoPreview).textContent = seoPreview.dataset.base + (slug || '…');
    };
    form.addEventListener('input', update);
}

// A form that waited for the identity check is sent again (auth/resume-action). form.submit()
// skips the data-confirm prompt: the user already answered it before confirming.
$$('form[data-auto-submit]').forEach((form) => form.submit());

// Flash messages can be dismissed.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-dismiss]');

    if (button) {
        button.closest('[data-dismissable]')?.remove();
    }
});

window.Biofy = Object.assign(window.Biofy || {}, { toast, initRepeaters });
