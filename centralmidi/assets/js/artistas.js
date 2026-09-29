/**
 * Central MIDI - Navegação e Busca de Artistas via AJAX
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $wrapper = $('.cmidi-artistas-page-wrapper');
        if (!$wrapper.length) return;

        var $grid = $('#cmidi-artistas-grid');
        var $loading = $('#cmidi-loading-indicator');
        var $letterBtns = $('.cmidi-letter-btn');
        var $searchInput = $('#cmidi-artist-search-input');
        var $clearBtn = $('#cmidi-clear-search-btn');
        var $headingLetter = $('#cmidi-active-letter-label');
        var $headingText = $('#cmidi-active-heading');
        var $countBadge = $('#cmidi-active-count');

        var currentLetter = $('.cmidi-letter-btn.is-active').data('letter') || 'A';
        var searchTimeout = null;
        var activeAjax = null;

        // Função para carregar artistas via AJAX
        function loadArtistas(params, pushUrl) {
            if (activeAjax && activeAjax.readyState !== 4) {
                activeAjax.abort();
            }

            $loading.fadeIn(150);
            $grid.css('opacity', '0.4');

            params.action = 'cmidi_get_artistas';
            params.nonce = cmidiArtistasData.nonce;

            activeAjax = $.ajax({
                url: cmidiArtistasData.ajax_url,
                type: 'POST',
                data: params,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $grid.html(response.data.html);
                        $countBadge.text(response.data.total + ' artistas encontrados');

                        if (params.search) {
                            $headingText.html('Resultados para <span class="cmidi-accent">"' + $('<div>').text(params.search).html() + '"</span>');
                        } else {
                            $headingText.html('Artistas com a letra <span class="cmidi-accent" id="cmidi-active-letter-label">' + response.data.letra + '</span>');
                        }

                        // Scroll suave até o início do grupo se estiver mais abaixo
                        var gridOffset = $('.cmidi-results-status-bar').offset().top - 180;
                        if ($(window).scrollTop() > gridOffset) {
                            $('html, body').animate({ scrollTop: gridOffset }, 300);
                        }
                    }
                },
                complete: function() {
                    $loading.fadeOut(150);
                    $grid.css('opacity', '1');
                }
            });

            if (pushUrl && window.history && window.history.pushState) {
                window.history.pushState({ letter: params.letra || '', search: params.search || '' }, '', pushUrl);
            }
        }

        // Clique em Letra do Alfabeto
        $letterBtns.on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            if ($btn.prop('disabled') || $btn.hasClass('is-disabled') || $btn.hasClass('is-active')) {
                return;
            }

            var letter = $btn.data('letter');
            var url = $btn.data('url');

            $letterBtns.removeClass('is-active');
            $btn.addClass('is-active');

            currentLetter = letter;
            $searchInput.val('');
            $clearBtn.hide();

            loadArtistas({ letra: letter }, url);
        });

        // Busca em Tempo Real (Debounce)
        $searchInput.on('input', function() {
            var query = $.trim($(this).val());

            if (query.length > 0) {
                $clearBtn.show();
            } else {
                $clearBtn.hide();
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                if (query.length >= 2) {
                    $letterBtns.removeClass('is-active');
                    loadArtistas({ search: query }, null);
                } else if (query.length === 0) {
                    // Restaurar letra ativa
                    var $activeBtn = $letterBtns.filter('[data-letter="' + currentLetter + '"]');
                    if ($activeBtn.length) {
                        $activeBtn.addClass('is-active');
                        loadArtistas({ letra: currentLetter }, $activeBtn.data('url'));
                    }
                }
            }, 280);
        });

        // Botão Limpar Busca
        $clearBtn.on('click', function() {
            $searchInput.val('').trigger('input');
            $(this).hide();
            $searchInput.focus();
        });

        // Suporte a Voltar/Avançar do Navegador (History API)
        $(window).on('popstate', function(e) {
            var path = window.location.pathname.replace(/^\/|\/$/g, '');
            var parts = path.split('/');
            var letter = 'A';

            if (parts.length >= 2 && (parts[0] === 'artistas' || parts[0] === 'midis')) {
                letter = parts[1] === 'outros' ? '#' : parts[1].toUpperCase();
            }

            currentLetter = letter;
            $letterBtns.removeClass('is-active');
            $letterBtns.filter('[data-letter="' + letter + '"]').addClass('is-active');
            $searchInput.val('');
            $clearBtn.hide();

            loadArtistas({ letra: letter }, null);
        });
    });

})(jQuery);
