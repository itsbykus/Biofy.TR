// SVG uploads are turned into PNG in the browser before any upload handler sees them; the
// server then stores a WebP like for every other image. The server never parses SVG (it can
// carry scripts), and drawing it through an <img> never runs any script inside it.

const MAX_SIDE = 2000;

function isSvg(file) {
    return file && (file.type === 'image/svg+xml' || /\.svgz?$/i.test(file.name));
}

/** Intrinsic size from width/height or the viewBox; SVGs without one get a square canvas. */
function svgSize(text) {
    const root = text.match(/<svg\b[^>]*>/i)?.[0] || '';
    const attr = (name) => parseFloat(root.match(new RegExp(`\\s${name}\\s*=\\s*["']?([\\d.]+)`, 'i'))?.[1]);
    const viewBox = root.match(/viewBox\s*=\s*["']\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)/i);
    const width = attr('width') || (viewBox ? parseFloat(viewBox[1]) : 0);
    const height = attr('height') || (viewBox ? parseFloat(viewBox[2]) : 0);

    return width > 0 && height > 0 ? { width, height } : { width: 1024, height: 1024 };
}

export async function svgToPng(file) {
    const text = await file.text();
    let { width, height } = svgSize(text);
    // Scale small icons up and huge drawings down so the result stays sharp but bounded.
    const scale = Math.min(MAX_SIDE / Math.max(width, height), Math.max(1, 1024 / Math.max(width, height)));
    width = Math.max(1, Math.round(width * scale));
    height = Math.max(1, Math.round(height * scale));

    const url = URL.createObjectURL(new Blob([text], { type: 'image/svg+xml' }));

    try {
        const image = new Image(width, height);
        image.decoding = 'async';
        image.src = url;
        await image.decode();

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d').drawImage(image, 0, 0, width, height);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

        if (!blob) {
            throw new Error('canvas');
        }

        return new File([blob], file.name.replace(/\.svgz?$/i, '') + '.png', { type: 'image/png' });
    } finally {
        URL.revokeObjectURL(url);
    }
}

// Capture phase: runs before the preview/upload listeners, which then see the PNG.
document.addEventListener(
    'change',
    async (event) => {
        const input = event.target;

        if (!(input instanceof HTMLInputElement) || input.type !== 'file' || !isSvg(input.files?.[0])) {
            return;
        }

        event.stopImmediatePropagation();

        try {
            const png = await svgToPng(input.files[0]);
            const transfer = new DataTransfer();
            transfer.items.add(png);
            input.files = transfer.files;
        } catch {
            input.value = '';
            window.Biofy?.toast?.(input.dataset.svgError || document.documentElement.dataset.svgError || 'SVG could not be converted.', 'error');

            return;
        }

        input.dispatchEvent(new Event('change', { bubbles: true }));
    },
    true,
);
