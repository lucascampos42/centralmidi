/**
 * AJAX Add to Cart & Toast Notification - Central MIDI
 */
document.addEventListener('DOMContentLoaded', () => {
    let toastContainer = document.getElementById('cm-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'cm-toast-container';
        toastContainer.className = 'cm-toast-container';
        document.body.appendChild(toastContainer);
    }

    function showToast(options = {}) {
        const {
            title = 'Arquivo MIDI',
            artist = 'Central MIDI',
            price = '',
            message = 'Música adicionada ao seu carrinho!',
            cartUrl = '/carrinho/'
        } = options;

        const toast = document.createElement('div');
        toast.className = 'cm-toast';

        toast.innerHTML = `
            <div class="cm-toast-icon">
                <i class="ri-check-line"></i>
            </div>
            <div class="cm-toast-body">
                <div class="cm-toast-msg">${message}</div>
                <div class="cm-toast-track"><strong>${title}</strong>${artist ? ' — ' + artist : ''}</div>
                <div class="cm-toast-actions">
                    <a href="${cartUrl}" class="cm-toast-btn-primary">
                        <i class="ri-shopping-cart-2-line"></i> Ver Carrinho
                    </a>
                </div>
            </div>
            <button type="button" class="cm-toast-close" title="Fechar" aria-label="Fechar notificação">
                <i class="ri-close-line"></i>
            </button>
        `;

        toastContainer.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Close button handler
        const closeBtn = toast.querySelector('.cm-toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                dismissToast(toast);
            });
        }

        // Auto dismiss after 4.5 seconds
        const timer = setTimeout(() => {
            dismissToast(toast);
        }, 4500);

        function dismissToast(el) {
            clearTimeout(timer);
            el.classList.remove('show');
            el.classList.add('hide');
            setTimeout(() => {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 300);
        }
    }

    function updateCartCount(count) {
        document.querySelectorAll('.cm-cart-count').forEach(badge => {
            badge.textContent = count;
            if (count > 0) {
                badge.style.display = 'inline-flex';
                badge.classList.remove('cm-visually-hidden');
            } else {
                badge.style.display = 'none';
            }
        });
    }

    // Intercept buy buttons
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.cm-btn-buy, a.add_to_cart_button');
        if (!btn) return;

        const href = btn.getAttribute('href') || '';
        const match = href.match(/add-to-cart=(\d+)/);
        if (!match) return;

        e.preventDefault();
        e.stopPropagation();

        const productId = match[1];
        const card = btn.closest('.cm-track-card') || btn.closest('.centralmidi-card') || btn.closest('.cm-card');
        const title = card ? (card.dataset.title || card.querySelector('.cm-track-title')?.textContent?.trim()) : 'Arquivo MIDI';
        const artist = card ? (card.dataset.artist || card.querySelector('.cm-artist-tag')?.textContent?.trim()) : '';

        // Add loading state
        const originalText = btn.innerHTML;
        btn.classList.add('loading');
        btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i>';

        try {
            const formData = new FormData();
            formData.append('add-to-cart', productId);
            formData.append('quantity', '1');

            const res = await fetch('/?wc-ajax=add_to_cart', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await res.json();

            if (data && data.fragments) {
                // If cart fragment updated
                const cartFrag = data.fragments['span.cm-cart-count'];
                if (cartFrag) {
                    const temp = document.createElement('div');
                    temp.innerHTML = cartFrag;
                    const newCount = temp.textContent.trim();
                    updateCartCount(newCount);
                } else {
                    // Increment count manually if fragment wasn't parsed
                    const currentBadge = document.querySelector('.cm-cart-count');
                    const currentCount = parseInt(currentBadge ? currentBadge.textContent : '0', 10) || 0;
                    updateCartCount(currentCount + 1);
                }

                showToast({
                    title: title || 'Arquivo MIDI',
                    artist: artist || '',
                    message: 'Música adicionada ao seu carrinho!',
                    cartUrl: '/carrinho/'
                });
            } else {
                // Fallback direct redirect if AJAX failed
                window.location.href = href;
            }
        } catch (err) {
            console.error('AJAX add-to-cart error:', err);
            window.location.href = href;
        } finally {
            btn.classList.remove('loading');
            btn.innerHTML = originalText;
        }
    });

    // Expose showToast globally
    window.CentralMidiToast = {
        show: showToast
    };
});
