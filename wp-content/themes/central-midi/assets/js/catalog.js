/**
 * Instant AJAX Filtering & Pagination for Central MIDI Catalog (/midis/)
 */
document.addEventListener('DOMContentLoaded', () => {
    const catalogContainer = document.getElementById('midis') || document.querySelector('.centralmidi-catalogo');
    if (!catalogContainer) return;

    let isFetching = false;

    async function loadCatalog(url, pushHistory = true) {
        if (isFetching) return;
        isFetching = true;

        catalogContainer.classList.add('cm-loading');

        try {
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) {
                window.location.href = url;
                return;
            }

            const htmlText = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');

            const newCatalog = doc.getElementById('midis') || doc.querySelector('.centralmidi-catalogo');
            if (newCatalog) {
                catalogContainer.innerHTML = newCatalog.innerHTML;

                if (pushHistory && url !== window.location.href) {
                    window.history.pushState({ url: url }, '', url);
                }

                // Scroll to top of catalog smoothly
                const rect = catalogContainer.getBoundingClientRect();
                if (rect.top < 0) {
                    window.scrollTo({
                        top: window.scrollY + rect.top - 80,
                        behavior: 'smooth'
                    });
                }
            } else {
                window.location.href = url;
            }
        } catch (err) {
            console.error('AJAX catalog filter error:', err);
            window.location.href = url;
        } finally {
            isFetching = false;
            catalogContainer.classList.remove('cm-loading');
        }
    }

    // Intercept form submit and select changes
    catalogContainer.addEventListener('submit', (e) => {
        const form = e.target.closest('form.centralmidi-filters');
        if (!form) return;
        e.preventDefault();

        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                params.set(key, value.trim());
            }
        }

        const baseUrl = form.getAttribute('action') || window.location.pathname;
        const targetUrl = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;

        loadCatalog(targetUrl);
    });

    // Auto filter on select change
    catalogContainer.addEventListener('change', (e) => {
        const select = e.target.closest('form.centralmidi-filters select');
        if (!select) return;

        const form = select.closest('form.centralmidi-filters');
        if (!form) return;

        const submitEvent = new Event('submit', { cancelable: true, bubbles: true });
        form.dispatchEvent(submitEvent);
    });

    // Intercept pagination & clear filter links
    catalogContainer.addEventListener('click', (e) => {
        const link = e.target.closest('.centralmidi-pagination a, .centralmidi-btn-clear');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#')) return;

        e.preventDefault();
        loadCatalog(href);
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', (e) => {
        if (window.location.pathname.includes('/midis')) {
            loadCatalog(window.location.href, false);
        }
    });
});
