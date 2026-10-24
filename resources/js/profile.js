import './tracking';

// Public bio page. Third-party players (YouTube, Spotify, maps...) are only loaded
// after the visitor asks for them, so no third-party request happens on page view.

document.querySelectorAll('[data-embed]').forEach((box) => {
    const button = box.querySelector('.bp-embed-load');

    button?.addEventListener('click', () => {
        const iframe = document.createElement('iframe');
        iframe.src = box.dataset.embed;
        iframe.title = box.dataset.title || '';
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen; clipboard-write';
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups allow-presentation allow-popups-to-escape-sandbox');

        if (box.dataset.height) {
            box.style.setProperty('--h', box.dataset.height + 'px');
        }

        box.replaceChildren(iframe);
    });
});

const share = document.querySelector('[data-share]');

share?.addEventListener('click', async () => {
    const url = share.dataset.share;

    if (navigator.share) {
        try {
            await navigator.share({ title: document.title, url });

            return;
        } catch {
            // dismissed — fall through to copy
        }
    }

    try {
        await navigator.clipboard.writeText(url);
        const toast = document.createElement('div');
        toast.className = 'bp-toast';
        toast.setAttribute('role', 'status');
        toast.textContent = share.dataset.copied || 'Copied';
        document.body.append(toast);
        setTimeout(() => toast.remove(), 2500);
    } catch {
        window.prompt('', url);
    }
});
