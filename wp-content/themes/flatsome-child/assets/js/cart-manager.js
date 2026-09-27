/**
 * Central Midi - Cart Management (Vanilla JS - Alta Performance)
 */

(function() {
    'use strict';

    // Cache localStorage key
    var CART_CACHE_KEY = 'cmidi_cart_ids';
    var inCartText = 'No carrinho';

    /**
     * Obtém IDs do carrinho - tenta localStorage primeiro (mais rápido que PHP)
     */
    function getCartIds() {
        var cached = localStorage.getItem(CART_CACHE_KEY);
        if (cached) {
            try {
                return JSON.parse(cached);
            } catch(e) {}
        }
        // Fallback para dados do PHP
        return (window.centralMidiCart && window.centralMidiCart.cartProductIds) ? window.centralMidiCart.cartProductIds : [];
    }

    /**
     * Salva IDs no localStorage
     */
    function setCartIds(ids) {
        try {
            localStorage.setItem(CART_CACHE_KEY, JSON.stringify(ids));
        } catch(e) {}
    }

    /**
     * Converte NodeList para Array (mais rápido)
     */
    function getCartButtons() {
        return document.querySelectorAll('.add_to_cart_button[data-product_id]');
    }

    /**
     * Marca botão como "No carrinho" - Vanilla JS
     */
    function markAsInCart(button) {
        if (!button || button.classList.contains('in-cart-button')) return;
        
        button.classList.remove('add_to_cart_button', 'ajax_add_to_cart', 'text_replaceable');
        button.classList.add('in-cart-button');
        button.textContent = inCartText;
        
        // Remove atributos desnecessários
        button.removeAttribute('data-quantity');
        button.removeAttribute('data-product_sku');
        button.removeAttribute('rel');
        
        // Atualiza URL para carrinho
        var pid = button.dataset.product_id;
        if (pid && window.centralMidiCart && window.centralMidiCart.cartUrl) {
            button.href = window.centralMidiCart.cartUrl;
        }
    }

    /**
     * Marca botão como "Comprar" - Vanilla JS
     */
    function markAsToBuy(button, productId) {
        if (!button) return;
        
        button.classList.remove('in-cart-button');
        button.classList.add('add_to_cart_button', 'ajax_add_to_cart');
        button.textContent = 'Comprar';
        button.href = '?add-to-cart=' + productId;
    }

    /**
     * Marca produtos que já estão no carrinho - OTIMIZADO
     */
    function markExistingCartItems() {
        var ids = getCartIds();
        if (!ids || !ids.length) return;
        
        var buttons = getCartButtons();
        var len = buttons.length;
        
        for (var i = 0; i < len; i++) {
            var btn = buttons[i];
            var pid = Number(btn.dataset.product_id);
            
            if (ids.includes(pid)) {
                markAsInCart(btn);
            }
        }
    }

    /**
     * Feedback visual instantâneo ao clicar - ANTES do AJAX
     */
    function showAddFeedback(button) {
        if (!button || button.classList.contains('in-cart-button')) return;
        
        // Feedback imediato
        button.classList.add('adding');
        button.style.opacity = '0.6';
        button.style.pointerEvents = 'none';
    }

    /**
     * Remove feedback visual
     */
    function removeFeedback(button) {
        if (button) {
            button.classList.remove('adding');
            button.style.opacity = '';
            button.style.pointerEvents = '';
        }
    }

    /**
     * Event delegation - um listener para toda a página (mais eficiente)
     */
    function initEventListeners() {
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.add_to_cart_button');
            
            if (btn && !btn.classList.contains('in-cart-button')) {
                // Feedback instantâneo
                showAddFeedback(btn);
            }
        }, { capture: true });

        document.addEventListener('added_to_cart', function(e) {
            var fragments = e.detail ? e.detail[0] : null;
            var button = e.detail && e.detail[3] ? e.detail[3][0] : null;
            
            if (button) {
                var pid = Number(button.dataset.product_id) || 
                        Number(button.getAttribute('data-product_id'));
                
                if (pid) {
                    markAsInCart(button);
                    
                    // Atualiza cache local
                    var ids = getCartIds();
                    if (!ids.includes(pid)) {
                        ids.push(pid);
                        setCartIds(ids);
                    }
                }
            }
            
            // Marca todos os botões com mesmo product_id
            var pid = button ? Number(button.dataset.product_id) : 0;
            if (pid) {
                var buttons = document.querySelectorAll('[data-product_id="' + pid + '"]');
                buttons.forEach(function(btn) {
                    markAsInCart(btn);
                });
            }
        });

        document.addEventListener('removed_from_cart', function(e) {
            // Tenta pegar o product_id removido
            var button = e.detail && e.detail[3] ? e.detail[3][0] : null;
            var pid = button ? Number(button.dataset.product_id || button.getAttribute('data-product_id')) : null;
            
            if (!pid) {
                // Recarrega a página para obter IDs corretos
                return;
            }
            
            // Reverte todos os botões com esse ID
            var buttons = document.querySelectorAll('[data-product_id="' + pid + '"].in-cart-button');
            buttons.forEach(function(btn) {
                markAsToBuy(btn, pid);
            });
            
            // Atualiza cache local
            var ids = getCartIds();
            var index = ids.indexOf(pid);
            if (index > -1) {
                ids.splice(index, 1);
                setCartIds(ids);
            }
        });
    }

    /**
     * Feedback visual na remoção do carrinho - instantâneo
     */
    function initRemoveFeedback() {
        var removeLinks = document.querySelectorAll('.woocommerce-cart-form .product-remove a.remove');
        
        for (var i = 0; i < removeLinks.length; i++) {
            removeLinks[i].addEventListener('click', function(e) {
                var row = this.closest('tr.cart_item');
                if (row) {
                    // Feedback imediato ANTES do server response
                    row.style.opacity = '0.3';
                    row.style.pointerEvents = 'none';
                }
            });
        }
    }

    /**
     * Inicialização
     */
    function init() {
        markExistingCartItems();
        initEventListeners();
        initRemoveFeedback();
    }

    // Executa quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();