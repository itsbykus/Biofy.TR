// Bio page editor: AJAX saving with optimistic concurrency, live preview refresh,
// drag & drop / keyboard reordering, block dialogs, media uploads and multi-tab warning.
import { initRepeaters, toast } from './app.js';

const editor = document.querySelector('[data-editor-root]');

if (editor) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const profileId = editor.dataset.profileId;
    const t = JSON.parse(editor.dataset.i18n || '{}');
    const preview = document.querySelector('#preview-frame');
    const statusPill = document.querySelector('[data-save-status]');
    let revision = editor.dataset.revision;
    let dirty = false;

    const setStatus = (text, state = 'idle') => {
        if (!statusPill) {
            return;
        }

        statusPill.textContent = text;
        statusPill.dataset.state = state;
    };

    const syncRevision = (value) => {
        revision = String(value);
        editor.dataset.revision = revision;
        document.querySelectorAll('input[name="revision"]').forEach((input) => {
            input.value = revision;
        });
    };

    const refreshPreview = () => {
        if (preview) {
            preview.src = preview.dataset.src + '?t=' + Date.now();
        }
    };

    const showConflict = () => {
        document.querySelector('#editor-conflict')?.removeAttribute('hidden');
        setStatus(t.conflict || 'Conflict', 'error');
    };

    const clearErrors = (form) => {
        form.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
            el.hidden = true;
        });
        form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
    };

    const showErrors = (form, errors) => {
        let first = null;

        for (const [key, messages] of Object.entries(errors || {})) {
            // "data.items.0.url" -> data[items][0][url]
            const parts = key.split('.');
            const name = parts[0] + parts.slice(1).map((p) => `[${p}]`).join('');
            const field = form.querySelector(`[name="${CSS.escape(name)}"]`);
            const slot = form.querySelector(`[data-error-for="${CSS.escape(key)}"]`);

            field?.setAttribute('aria-invalid', 'true');

            if (slot) {
                slot.textContent = messages[0];
                slot.hidden = false;
            }

            first ??= messages[0];
        }

        if (first) {
            toast(first, 'error');
        }
    };

    const applyFragments = (html) => {
        for (const [target, markup] of Object.entries(html || {})) {
            const container = document.querySelector(`[data-fragment="${target}"]`);

            if (container) {
                container.innerHTML = markup;
                initSortables(container);
                initRepeaters(container);
            }
        }
    };

    const applyState = (data) => {
        if (data.revision) {
            syncRevision(data.revision);
        }

        const badge = document.querySelector('[data-publish-state]');

        if (badge && data.status) {
            badge.dataset.status = data.status;
            badge.textContent = t['status_' + data.status] || data.status;
        }

        document.querySelector('[data-unpublished]')?.toggleAttribute('hidden', !data.dirty);
    };

    async function send(url, method, body) {
        if (body instanceof FormData) {
            body.set('revision', revision);
        }

        const response = await fetch(url, {
            method,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body,
            credentials: 'same-origin',
        });

        let data = {};

        try {
            data = await response.json();
        } catch {
            // non-JSON (e.g. redirect to the confirm-password page)
        }

        return { response, data };
    }

    // Any form marked data-editor-form is submitted in the background.
    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches('[data-editor-form]')) {
            return;
        }

        event.preventDefault();
        clearErrors(form);
        setStatus(t.saving || 'Saving…', 'saving');
        const submitter = event.submitter;
        submitter?.setAttribute('disabled', '');

        try {
            const formData = new FormData(form, submitter);
            const method = (formData.get('_method') || form.method || 'POST').toUpperCase();
            formData.set('_method', method);
            const { response, data } = await send(form.action, 'POST', formData);

            if (response.status === 409) {
                showConflict();
            } else if (response.status === 422) {
                showErrors(form, data.errors || { _: [data.message] });
                setStatus(t.error || 'Error', 'error');
            } else if (response.status === 423 || response.redirected) {
                window.location.reload();
            } else if (response.ok) {
                applyFragments(data.html);
                applyState(data);
                setStatus(t.saved || 'Saved', 'saved');
                toast(data.message || t.saved);
                dirty = false;
                form.closest('dialog')?.close();
                refreshPreview();
            } else {
                setStatus(t.error || 'Error', 'error');
                toast(data.message || t.error || 'Error', 'error');
            }
        } catch {
            setStatus(t.offline || 'Offline', 'error');
            toast(t.offline || 'Network error', 'error');
        } finally {
            submitter?.removeAttribute('disabled');
        }
    });

    // Load a server-rendered block form into the dialog (add or edit).
    const dialog = document.querySelector('#block-dialog');

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-load-block-form]');

        if (!trigger || !dialog) {
            return;
        }

        event.preventDefault();
        document.querySelector('#block-picker')?.close();
        const body = dialog.querySelector('[data-dialog-body]');
        body.innerHTML = '<p class="p-6 text-sm text-zinc-500">' + (t.loading || '…') + '</p>';
        dialog.showModal();

        const response = await fetch(trigger.dataset.loadBlockForm, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
        body.innerHTML = response.ok ? await response.text() : '<p class="p-6 text-sm text-red-700">' + (t.error || 'Error') + '</p>';
        syncRevision(revision);
        initRepeaters(body);
        body.querySelector('input:not([type=hidden]), textarea, select')?.focus();
    });

    // Quick actions on list items (hide/show, duplicate, delete) use small forms; handled above.

    // Drag & drop + keyboard ordering for [data-sortable] lists.
    function initSortables(root = document) {
        root.querySelectorAll('[data-sortable]').forEach((list) => {
            if (list.dataset.bound) {
                return;
            }

            list.dataset.bound = '1';
            let dragged = null;

            const persist = async () => {
                const order = Array.from(list.querySelectorAll(':scope > [data-id]')).map((el) => el.dataset.id);
                const body = new FormData();
                order.forEach((id) => body.append('order[]', id));
                setStatus(t.saving || 'Saving…', 'saving');
                const { response, data } = await send(list.dataset.sortable, 'POST', body);

                if (response.status === 409) {
                    showConflict();
                } else if (response.ok) {
                    applyState(data);
                    setStatus(t.saved || 'Saved', 'saved');
                    refreshPreview();
                } else {
                    toast(data.message || t.error || 'Error', 'error');
                }
            };

            list.addEventListener('dragstart', (event) => {
                dragged = event.target.closest('[data-id]');

                if (!dragged || !event.target.closest('[draggable="true"]')) {
                    return;
                }

                dragged.classList.add('sortable-ghost');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', dragged.dataset.id);
            });

            list.addEventListener('dragover', (event) => {
                if (!dragged) {
                    return;
                }

                event.preventDefault();
                const over = event.target.closest(':scope > [data-id]');

                if (over && over !== dragged && over.parentElement === list) {
                    const rect = over.getBoundingClientRect();
                    const after = event.clientY > rect.top + rect.height / 2;
                    over.parentElement.insertBefore(dragged, after ? over.nextSibling : over);
                }
            });

            list.addEventListener('dragend', () => {
                if (!dragged) {
                    return;
                }

                dragged.classList.remove('sortable-ghost');
                dragged = null;
                persist();
            });

            // Keyboard / button alternative to drag & drop (accessibility).
            list.addEventListener('click', (event) => {
                const move = event.target.closest('[data-move]');

                if (!move) {
                    return;
                }

                const item = move.closest('[data-id]');
                const sibling = move.dataset.move === 'up' ? item.previousElementSibling : item.nextElementSibling;

                if (sibling) {
                    move.dataset.move === 'up' ? list.insertBefore(item, sibling) : list.insertBefore(sibling, item);
                    move.focus();
                    persist();
                }
            });
        });
    }

    initSortables();

    // Media fields: upload a file (auto) or pick one from the library.
    document.addEventListener('change', async (event) => {
        const input = event.target;

        if (!input.matches('[data-media-upload]') || !input.files.length) {
            return;
        }

        const field = input.closest('[data-media-field]');
        const body = new FormData();
        body.append('file', input.files[0]);
        body.append('purpose', field.dataset.purpose || 'block');
        setStatus(t.uploading || 'Uploading…', 'saving');

        const response = await fetch(field.dataset.uploadUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body,
        });
        const data = await response.json().catch(() => ({}));
        input.value = '';

        if (!response.ok) {
            setStatus(t.error || 'Error', 'error');
            toast(data.message || (data.errors && Object.values(data.errors)[0][0]) || t.error, 'error');

            return;
        }

        setMedia(field, data.id, data.url);
        setStatus(t.uploaded || 'Uploaded', 'saved');
    });

    document.addEventListener('click', (event) => {
        const pick = event.target.closest('[data-media-pick]');

        if (pick) {
            event.preventDefault();
            setMedia(pick.closest('[data-media-field]'), pick.dataset.mediaPick, pick.dataset.url);
            pick.closest('details')?.removeAttribute('open');
        }

        const clear = event.target.closest('[data-media-clear]');

        if (clear) {
            event.preventDefault();
            setMedia(clear.closest('[data-media-field]'), '', '');
        }
    });

    function setMedia(field, id, url) {
        field.querySelector('input[type="hidden"]').value = id;
        const img = field.querySelector('[data-media-preview]');

        if (img) {
            img.src = url || '';
            img.hidden = !url;
        }

        field.querySelector('[data-media-empty]')?.toggleAttribute('hidden', Boolean(url));
        dirty = true;
    }

    // Preview device switch.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-device]');

        if (!button || !preview) {
            return;
        }

        document.querySelectorAll('[data-device]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
        preview.parentElement.dataset.device = button.dataset.device;
    });

    document.querySelector('[data-refresh-preview]')?.addEventListener('click', refreshPreview);

    // Unsaved-changes guard.
    document.addEventListener('input', (event) => {
        if (event.target.closest('[data-editor-form]')) {
            dirty = true;
            setStatus(t.unsaved || 'Unsaved changes', 'dirty');
        }
    });

    window.addEventListener('beforeunload', (event) => {
        if (dirty) {
            event.preventDefault();
        }
    });

    document.addEventListener('submit', (event) => {
        if (!event.target.matches('[data-editor-form]')) {
            dirty = false;
        }
    });

    // Warn when the same page is open in another tab (edits there would conflict).
    if ('BroadcastChannel' in window) {
        const channel = new BroadcastChannel('biofy-editor-' + profileId);
        const tabId = Math.random().toString(36).slice(2);

        channel.onmessage = (message) => {
            if (message.data.tab === tabId) {
                return;
            }

            document.querySelector('#editor-multitab')?.removeAttribute('hidden');

            if (message.data.type === 'hello') {
                channel.postMessage({ type: 'here', tab: tabId });
            }
        };

        channel.postMessage({ type: 'hello', tab: tabId });
    }
}
