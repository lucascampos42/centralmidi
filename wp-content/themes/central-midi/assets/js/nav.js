document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('cm-menu-toggle');
    const nav = document.getElementById('cm-primary-nav');
    if (!toggle || !nav) {
        return;
    }

    const mobileQuery = window.matchMedia('(max-width: 900px)');

    function setOpen(open) {
        nav.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
    }

    toggle.addEventListener('click', () => {
        setOpen(!nav.classList.contains('is-open'));
    });

    // Close the panel when a regular link is clicked.
    nav.querySelectorAll('.cm-nav-list a').forEach((link) => {
        if (!link.classList.contains('cm-dropdown-toggle')) {
            link.addEventListener('click', () => setOpen(false));
        }
    });

    // Submenu toggling on mobile (desktop uses hover).
    nav.querySelectorAll('.cm-dropdown-toggle').forEach((toggleLink) => {
        toggleLink.addEventListener('click', (e) => {
            if (!mobileQuery.matches) {
                return;
            }
            e.preventDefault();
            toggleLink.parentElement.classList.toggle('is-open');
        });
    });
});