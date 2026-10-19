import { $, $$ } from './util';

const normalize = (value) => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

/** Native category links also work without JavaScript. */
export function initMenu() {
    const input = $('[data-menu-input]');
    const sections = $$('[data-menu-section]');
    const links = $$('[data-menu-category]');
    const status = $('[data-menu-status]');
    const empty = $('[data-menu-empty]');
    const template = $('[data-menu-search]').dataset.msgResults || ':count';
    let category = links.find((link) => link.hasAttribute('aria-current'))?.dataset.menuCategory || 'cakes';

    const render = () => {
        const query = normalize(input.value);
        let total = 0;
        sections.forEach((section) => {
            section.hidden = section.id !== category;
            $$('[data-menu-item]', section).forEach((item) => {
                item.hidden = !normalize(item.dataset.name).includes(query);
                if (!section.hidden && !item.hidden) total++;
            });
        });
        links.forEach((link) => {
            const active = link.dataset.menuCategory === category;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
        empty.hidden = total > 0 || category === 'chocolates';
        status.textContent = links.find((link) => link.dataset.menuCategory === category).textContent.trim() + ': ' + template.replace(':count', total);
    };

    const followHash = () => {
        let target;
        try { target = document.getElementById(decodeURIComponent(location.hash.slice(1))); }
        catch { return; }
        const section = target?.closest('[data-menu-section]');
        if (section) {
            category = section.id;
            input.value = '';
            render();
            requestAnimationFrame(() => target.scrollIntoView({ block: 'start' }));
        }
    };

    links.forEach((link) => link.addEventListener('click', (event) => {
        if (event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        category = link.dataset.menuCategory;
        input.value = '';
        history.pushState(null, '', link.href);
        render();
    }));
    input.addEventListener('input', render);
    window.addEventListener('hashchange', followHash);
    window.addEventListener('popstate', () => {
        category = new URLSearchParams(location.search).get('category') || 'cakes';
        if (!sections.some((section) => section.id === category)) category = 'cakes';
        render();
        followHash();
    });
    render();
    followHash();
}
