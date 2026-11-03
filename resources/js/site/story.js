import { $, $$, finePointer } from './util';

/*
 * Our Story: the three moments open one at a time (hover, focus or tap).
 */

function initMoments(section) {
    const moments = $$('[data-moment]', section);
    const open = (moment) => moments.forEach((m) => {
        const on = m === moment;
        m.classList.toggle('is-active', on);
        $('.moment__tab', m).setAttribute('aria-expanded', on);
    });
    // Hover opens a panel only once the pointer rests on it, so passing across doesn't flick through them.
    let hover = 0;
    moments.forEach((moment) => {
        $('.moment__tab', moment).addEventListener('click', () => open(moment));
        if (finePointer) {
            moment.addEventListener('pointerenter', () => {
                clearTimeout(hover);
                hover = setTimeout(() => open(moment), 250);
            });
            moment.addEventListener('pointerleave', () => clearTimeout(hover));
        }
        moment.addEventListener('focusin', () => open(moment));
    });
}

export function initStory() {
    $$('[data-moments]').forEach(initMoments);
}
