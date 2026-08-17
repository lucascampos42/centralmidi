/**
 * Central MIDI - Theme Toggle (Light / Dark mode)
 */
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('cm-theme-toggle');
    const icon = document.getElementById('cm-theme-icon');
    if (!toggle || !icon) {
        return;
    }

    const STORAGE_KEY = 'cm-theme';

    function getInitialTheme() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'light' || saved === 'dark') {
                return saved;
            }
        } catch (e) {}
        return (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
    }

    function applyTheme(theme) {
        const root = document.documentElement;
        const isLight = theme === 'light';

        root.classList.toggle('cm-theme-light', isLight);
        root.classList.toggle('cm-theme-dark', !isLight);

        // Icon shows what the NEXT click will activate: Sun when in dark mode, Moon when in light mode
        icon.className = isLight ? 'ri-moon-line' : 'ri-sun-line';
        const label = isLight ? 'Alternar para tema escuro' : 'Alternar para tema claro';
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);

        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {}
    }

    let currentTheme = getInitialTheme();
    applyTheme(currentTheme);

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        currentTheme = currentTheme === 'light' ? 'dark' : 'light';
        applyTheme(currentTheme);
    });

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', (e) => {
            try {
                if (!localStorage.getItem(STORAGE_KEY)) {
                    currentTheme = e.matches ? 'light' : 'dark';
                    applyTheme(currentTheme);
                }
            } catch (err) {}
        });
    }
});