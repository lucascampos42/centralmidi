document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('cm-theme-toggle');
    const icon = document.getElementById('cm-theme-icon');
    if (!toggle || !icon) {
        return;
    }

    const STORAGE_KEY = 'cm-theme';
    const STATES = ['system', 'light', 'dark'];
    const ICONS = {
        system: 'ri-contrast-2-line',
        light: 'ri-sun-line',
        dark: 'ri-moon-line',
    };
    const LABELS = {
        system: 'Tema: automático',
        light: 'Tema: claro',
        dark: 'Tema: escuro',
    };

    function getState() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            return STATES.includes(saved) ? saved : 'system';
        } catch (e) {
            return 'system';
        }
    }

    function apply(state) {
        const root = document.documentElement;
        root.classList.toggle('cm-theme-light', state === 'light');
        root.classList.toggle('cm-theme-dark', state === 'dark');
        icon.className = ICONS[state] || ICONS.system;
        toggle.setAttribute('aria-label', LABELS[state] || LABELS.system);
        toggle.setAttribute('title', LABELS[state] || LABELS.system);
        try {
            if (state === 'system') {
                localStorage.removeItem(STORAGE_KEY);
            } else {
                localStorage.setItem(STORAGE_KEY, state);
            }
        } catch (e) {}
    }

    function next(state) {
        return STATES[(STATES.indexOf(state) + 1) % STATES.length];
    }

    apply(getState());

    toggle.addEventListener('click', () => {
        apply(next(getState()));
    });
});