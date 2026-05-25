/**
 * Home quote highlights.
 *
 * Crossfades through the featured-review pull-quotes in `.home-quotes` by
 * toggling `.is-visible` on each `.home-quotes__slide`; the CSS owns the
 * fade. Handles any number of quotes. With a single quote — or when the
 * visitor prefers reduced motion — it shows the first and stops.
 */

const INTERVAL_MS = 5000;

export function initHomeQuotes() {
    const container = document.querySelector('.home-quotes');
    if (!container) {
        return;
    }

    const slides = Array.from(container.querySelectorAll('.home-quotes__slide'));
    if (slides.length === 0) {
        return;
    }

    let index = 0;
    slides[index].classList.add('is-visible');

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (slides.length < 2 || reduceMotion) {
        return;
    }

    setInterval(() => {
        slides[index].classList.remove('is-visible');
        index = (index + 1) % slides.length;
        slides[index].classList.add('is-visible');
    }, INTERVAL_MS);
}
