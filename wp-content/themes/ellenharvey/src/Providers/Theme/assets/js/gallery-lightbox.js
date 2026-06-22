/**
 * Gallery lightbox.
 *
 * Each `.gallery-poster` in the Photos grid carries a sibling
 * `<script class="gallery-poster__data">` holding its production's photos as
 * JSON. Clicking a poster opens a single shared overlay that pages through
 * that set with prev/next, keyboard (←/→/Esc), and a caption.
 *
 * Styled to mirror the original site's classic Lightbox2: a white-framed
 * image, prev/next as hover zones over the image halves, and a data bar
 * below with the caption, an "Image X of Y" counter, and the close button.
 * No jQuery — this replaces the original's mootools + lightbox.js stack.
 */

export function initGalleryLightbox() {
    const posters = Array.from(document.querySelectorAll('.gallery-poster'));
    if (posters.length === 0) {
        return;
    }

    const overlay = buildOverlay();
    document.body.appendChild(overlay.root);

    let photos = [];
    let index = 0;
    let lastTrigger = null;
    let natural = null; // { w, h } of the photo currently shown

    // Size the white frame to the photo at its display scale: fit within 90vw
    // (minus the frame's 10px padding each side) and 72vh, never upscaling.
    // The frame and image-wrap have CSS transitions, so changing these sizes
    // animates — the Lightbox2 resize. Mirrors the CSS max-width/max-height.
    function fitFrame(nw, nh) {
        const maxW = window.innerWidth * 0.9 - 20;
        const maxH = window.innerHeight * 0.72;
        const scale = Math.min(maxW / nw, maxH / nh, 1);
        const w = Math.round(nw * scale);
        const h = Math.round(nh * scale);
        overlay.frame.style.width = w + 20 + 'px'; // + padding (border-box)
        overlay.imageWrap.style.height = h + 'px';
    }

    function show(i) {
        index = (i + photos.length) % photos.length;
        const photo = photos[index];
        overlay.caption.textContent = photo.caption || '';
        // Classic Lightbox2 counter wording.
        overlay.counter.textContent = `Image ${index + 1} of ${photos.length}`;
        const multiple = photos.length > 1;
        overlay.prev.hidden = !multiple;
        overlay.next.hidden = !multiple;

        // Preload to learn the photo's size, animate the frame to it, then
        // reveal — so the frame always hugs the photo (no leftover whitespace)
        // and the change is a smooth resize rather than a snap.
        const pre = new Image();
        pre.onload = () => {
            natural = { w: pre.naturalWidth, h: pre.naturalHeight };
            fitFrame(natural.w, natural.h);

            // Swap the source while hidden (transition disabled, so no flash of
            // the old image at the new size), then fade the new photo in — the
            // resize and fade run together, the way Lightbox2 reveals a slide.
            const img = overlay.image;
            img.style.transition = 'none';
            img.style.opacity = '0';
            img.src = pre.src;
            img.alt = photo.alt || '';
            void img.offsetWidth; // commit the hidden state before re-enabling
            img.style.transition = '';
            img.style.opacity = '1';
        };
        pre.src = photo.src;
    }

    function open(set, startAt) {
        photos = set;
        overlay.root.classList.add('is-open');
        overlay.root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-lightbox-open');
        show(startAt);
        overlay.close.focus();
    }

    function close() {
        overlay.root.classList.remove('is-open');
        overlay.root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('has-lightbox-open');
        overlay.image.src = '';
        if (lastTrigger) {
            lastTrigger.focus();
        }
    }

    posters.forEach((poster) => {
        poster.addEventListener('click', () => {
            const dataEl = poster.parentElement.querySelector('.gallery-poster__data');
            if (!dataEl) {
                return;
            }
            let set;
            try {
                set = JSON.parse(dataEl.textContent);
            } catch (e) {
                return;
            }
            if (!Array.isArray(set) || set.length === 0) {
                return;
            }
            lastTrigger = poster;
            open(set, 0);
        });
    });

    overlay.close.addEventListener('click', close);
    overlay.prev.addEventListener('click', () => show(index - 1));
    overlay.next.addEventListener('click', () => show(index + 1));
    overlay.backdrop.addEventListener('click', close);

    document.addEventListener('keydown', (e) => {
        if (!overlay.root.classList.contains('is-open')) {
            return;
        }
        if (e.key === 'Escape') {
            close();
        } else if (e.key === 'ArrowLeft') {
            show(index - 1);
        } else if (e.key === 'ArrowRight') {
            show(index + 1);
        }
    });

    // Keep the frame fitted to the current photo if the viewport changes.
    window.addEventListener('resize', () => {
        if (overlay.root.classList.contains('is-open') && natural) {
            fitFrame(natural.w, natural.h);
        }
    });
}

function buildOverlay() {
    const root = el('div', 'lightbox');
    root.setAttribute('aria-hidden', 'true');
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-label', 'Photo gallery');

    const backdrop = el('div', 'lightbox__backdrop');

    // The white frame holds the image (with hover prev/next zones over its
    // halves) and the data bar below — the Lightbox2 look.
    const frame = el('div', 'lightbox__frame');

    const imageWrap = el('div', 'lightbox__image-wrap');
    const image = document.createElement('img');
    image.className = 'lightbox__image';
    image.alt = '';

    const prev = navButton('lightbox__nav lightbox__nav--prev', 'Previous photo');
    const next = navButton('lightbox__nav lightbox__nav--next', 'Next photo');
    imageWrap.append(image, prev, next);

    const data = el('div', 'lightbox__data');
    const caption = el('p', 'lightbox__caption');

    const details = el('div', 'lightbox__details');
    const counter = el('p', 'lightbox__counter');
    const close = button('lightbox__close', 'Close', '×'); // ×
    details.append(counter, close);

    data.append(caption, details);
    frame.append(imageWrap, data);
    root.append(backdrop, frame);

    return { root, backdrop, frame, image, imageWrap, caption, counter, close, prev, next };
}

function el(tag, className) {
    const node = document.createElement(tag);
    node.className = className;
    return node;
}

function navButton(className, label) {
    const node = document.createElement('button');
    node.type = 'button';
    node.className = className;
    node.setAttribute('aria-label', label);
    // Arrow glyph lives in a span so CSS can fade it in on hover.
    const glyph = document.createElement('span');
    glyph.className = 'lightbox__nav-arrow';
    glyph.setAttribute('aria-hidden', 'true');
    glyph.textContent = className.includes('prev') ? '‹' : '›'; // ‹ ›
    node.appendChild(glyph);
    return node;
}

function button(className, label, glyph) {
    const node = document.createElement('button');
    node.type = 'button';
    node.className = className;
    node.setAttribute('aria-label', label);
    node.textContent = glyph;
    return node;
}
