/**
 * Gallery lightbox.
 *
 * Each `.gallery-poster` in the Photos grid carries a sibling
 * `<script class="gallery-poster__data">` holding its production's photos as
 * JSON. Clicking a poster opens a single shared overlay that pages through
 * that set with prev/next, keyboard (←/→/Esc), and a caption. No jQuery —
 * this replaces the original site's mootools + lightbox.js stack.
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

    function show(i) {
        index = (i + photos.length) % photos.length;
        const photo = photos[index];
        overlay.image.src = photo.src;
        overlay.image.alt = photo.alt || '';
        overlay.caption.textContent = photo.caption || '';
        overlay.counter.textContent = `${index + 1} / ${photos.length}`;
        const multiple = photos.length > 1;
        overlay.prev.hidden = !multiple;
        overlay.next.hidden = !multiple;
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

    let lastTrigger = null;

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
}

function buildOverlay() {
    const root = document.createElement('div');
    root.className = 'lightbox';
    root.setAttribute('aria-hidden', 'true');
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-label', 'Photo gallery');

    const backdrop = document.createElement('div');
    backdrop.className = 'lightbox__backdrop';

    const figure = document.createElement('figure');
    figure.className = 'lightbox__figure';

    const image = document.createElement('img');
    image.className = 'lightbox__image';
    image.alt = '';

    const caption = document.createElement('figcaption');
    caption.className = 'lightbox__caption';

    const counter = document.createElement('p');
    counter.className = 'lightbox__counter';

    const close = button('lightbox__close', 'Close', '×');
    const prev = button('lightbox__nav lightbox__nav--prev', 'Previous photo', '‹');
    const next = button('lightbox__nav lightbox__nav--next', 'Next photo', '›');

    figure.append(image, caption);
    root.append(backdrop, close, prev, figure, next, counter);

    return { root, backdrop, figure, image, caption, counter, close, prev, next };
}

function button(className, label, glyph) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = className;
    el.setAttribute('aria-label', label);
    el.textContent = glyph;
    return el;
}
