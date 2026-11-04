/*
 * Light / dark / system theme, remembered in this browser. The <head> applies
 * it before first paint (layouts/dashboard.blade.php); this keeps it in sync.
 */
const KEY = 'koba:theme';
const media = matchMedia('(prefers-color-scheme: dark)');

export const isDark = () => document.documentElement.classList.contains('dark');

export function preference() {
    try { return localStorage.getItem(KEY) || 'system'; } catch (e) { return 'system'; }
}

function apply(mode) {
    const dark = mode === 'dark' || (mode === 'system' && media.matches);
    const changed = dark !== isDark();
    document.documentElement.classList.toggle('dark', dark);
    if (changed) window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark } }));
}

export function setTheme(mode) {
    try { localStorage.setItem(KEY, mode); } catch (e) { /* storage blocked: lasts this page */ }
    apply(mode);
}

export function toggleTheme() {
    setTheme(isDark() ? 'light' : 'dark');
}

media.addEventListener('change', () => { if (preference() === 'system') apply('system'); });
