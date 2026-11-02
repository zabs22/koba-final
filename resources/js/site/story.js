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
    moments.forEach((moment) => {
        $('.moment__tab', moment).addEventListener('click', () => open(moment));
        if (finePointer) moment.addEventListener('pointerenter', () => open(moment));
        moment.addEventListener('focusin', () => open(moment));
    });
}

export function initStory() {
    $$('[data-moments]').forEach(initMoments);
}
