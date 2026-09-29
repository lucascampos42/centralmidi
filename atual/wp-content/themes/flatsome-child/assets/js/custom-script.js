/**
 * Central MIDI - Modern Audio Player Engine (HTML5 Native + Playlist Queue)
 */
(function() {
  'use strict';

  var audioElement = null;
  var playerBar = null;
  var playerTitle = null;
  var playerArtist = null;
  var playerCoverImg = null;
  var playerCoverPlaceholder = null;
  var playerBuyLink = null;
  var btnMainPlay = null;
  var iconMainPlay = null;
  var btnStop = null;
  var btnPrev = null;
  var btnNext = null;
  var btnClose = null;
  var btnTogglePlaylist = null;
  var btnClosePlaylist = null;
  var playlistDrawer = null;
  var playlistItemsContainer = null;
  var playlistCountEl = null;
  var playlistBadgeEl = null;
  var currentTimeEl = null;
  var durationTimeEl = null;
  var progressBar = null;
  var progressFill = null;
  var volumeSlider = null;
  var volumeIcon = null;

  var currentCard = null;
  var currentAudioUrl = '';
  var playlistQueue = [];
  var queueIndex = -1;

  function initPlayerElements() {
    audioElement = document.getElementById('cm-audio-element');
    playerBar = document.getElementById('cm-global-player');
    playerTitle = document.getElementById('cm-player-title');
    playerArtist = document.getElementById('cm-player-artist');
    playerCoverImg = document.getElementById('cm-player-thumb-img');
    playerCoverPlaceholder = document.getElementById('cm-player-thumb-placeholder');
    playerBuyLink = document.getElementById('cm-player-buy-link');
    btnMainPlay = document.getElementById('cm-btn-main-play');
    iconMainPlay = document.getElementById('cm-main-play-icon');
    btnStop = document.getElementById('cm-btn-stop');
    btnPrev = document.getElementById('cm-btn-prev');
    btnNext = document.getElementById('cm-btn-next');
    btnClose = document.getElementById('cm-btn-close-player');
    btnTogglePlaylist = document.getElementById('cm-btn-toggle-playlist');
    btnClosePlaylist = document.getElementById('cm-btn-close-playlist');
    playlistDrawer = document.getElementById('cm-playlist-drawer');
    playlistItemsContainer = document.getElementById('cm-playlist-items');
    playlistCountEl = document.getElementById('cm-playlist-count');
    playlistBadgeEl = document.getElementById('cm-playlist-badge');
    currentTimeEl = document.getElementById('cm-current-time');
    durationTimeEl = document.getElementById('cm-duration-time');
    progressBar = document.getElementById('cm-progress-bar');
    progressFill = document.getElementById('cm-progress-fill');
    volumeSlider = document.getElementById('cm-volume-slider');
    volumeIcon = document.getElementById('cm-volume-icon');

    // Restore volume from localStorage
    if (audioElement && volumeSlider) {
      var savedVolume = localStorage.getItem('cm-player-volume');
      var initialVol = savedVolume !== null ? parseFloat(savedVolume) : 0.8;
      audioElement.volume = initialVol;
      volumeSlider.value = initialVol;
      updateVolumeIcon(initialVol);
    }
  }

  function formatTime(seconds) {
    if (isNaN(seconds) || seconds < 0) return '00:00';
    var mins = Math.floor(seconds / 60);
    var secs = Math.floor(seconds % 60);
    return (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
  }

  function updatePlayIcon(isPlaying) {
    if (iconMainPlay) {
      iconMainPlay.className = isPlaying ? 'ri-pause-fill' : 'ri-play-fill';
    }
  }

  function updateVolumeIcon(val) {
    if (!volumeIcon) return;
    if (val === 0) {
      volumeIcon.className = 'ri-volume-mute-line';
    } else if (val < 0.5) {
      volumeIcon.className = 'ri-volume-down-line';
    } else {
      volumeIcon.className = 'ri-volume-up-line';
    }
  }

  function setCardPlayingState(card, isPlaying) {
    if (!card) return;
    card.classList.toggle('playing', isPlaying);
    var triggers = card.querySelectorAll ? card.querySelectorAll('.cm-play-trigger, .button-play') : [card];
    triggers.forEach(function(trig) {
      trig.classList.toggle('playing', isPlaying);
      var label = isPlaying ? 'Pausar Demonstração' : 'Ouvir Demo';
      trig.setAttribute('title', label);
      trig.setAttribute('aria-label', label);
      var textSpan = trig.querySelector('.cm-play-text');
      if (textSpan) {
        var baseSpan = textSpan.querySelector('.cm-play-text-base');
        var extraSpan = textSpan.querySelector('.cm-play-text-extra');
        if (baseSpan) {
          baseSpan.textContent = isPlaying ? 'Pausar' : 'Ouvir';
          if (extraSpan) {
            extraSpan.textContent = isPlaying ? '' : ' Demo';
          }
        } else {
          textSpan.innerHTML = isPlaying ? '<span class="cm-play-text-base">Pausar</span>' : '<span class="cm-play-text-base">Ouvir</span><span class="cm-play-text-extra"> Demo</span>';
        }
      }
    });
  }

  function updateMediaSession(title, artist) {
    if ('mediaSession' in navigator) {
      try {
        navigator.mediaSession.metadata = new MediaMetadata({
          title: title || 'Central MIDI',
          artist: artist || 'Demonstração',
          album: 'Central MIDI',
          artwork: [
            { src: window.location.origin + '/wp-content/themes/flatsome-child/assets/img/logo.png', sizes: '512x512', type: 'image/png' }
          ]
        });

        navigator.mediaSession.setActionHandler('play', function() {
          if (audioElement && audioElement.src) audioElement.play();
        });
        navigator.mediaSession.setActionHandler('pause', function() {
          if (audioElement) audioElement.pause();
        });
        navigator.mediaSession.setActionHandler('previoustrack', playPrevTrack);
        navigator.mediaSession.setActionHandler('nexttrack', playNextTrack);
        navigator.mediaSession.setActionHandler('seekto', function(details) {
          if (details.seekTime !== undefined && audioElement) {
            audioElement.currentTime = details.seekTime;
          }
        });
      } catch (e) {}
    }
  }

  function extractTrackData(source) {
    var audioUrl = '';
    var title = 'Música Sem Título';
    var artist = 'Central MIDI';
    var productUrl = '#';
    var productId = '';
    var thumbUrl = '';
    var card = null;

    if (source instanceof HTMLElement) {
      card = source.closest('.cm-track-card') || source.closest('.product') || source.closest('.col') || source;
      audioUrl = source.getAttribute('data-audio') || (card && card.getAttribute('data-audio')) || '';
      title = source.getAttribute('data-title') || (card && card.getAttribute('data-title'));
      if (!title && card) {
        var titleEl = card.querySelector('.cm-track-title, .woocommerce-loop-product__title, h2, h3, h4');
        if (titleEl) title = titleEl.textContent.trim();
      }
      artist = source.getAttribute('data-artist') || (card && card.getAttribute('data-artist'));
      if (!artist && card) {
        var artistEl = card.querySelector('.cm-artist-tag');
        if (artistEl) artist = artistEl.textContent.trim();
      }
      productUrl = source.getAttribute('data-url') || (card && card.getAttribute('data-url')) || '#';
      productId = source.getAttribute('data-id') || (card && (card.getAttribute('data-id') || card.getAttribute('data-product-id'))) || '';

      var thumbEl = card ? card.querySelector('.cm-card-cover img, .attachment-woocommerce_thumbnail') : null;
      if (thumbEl && thumbEl.src) thumbUrl = thumbEl.src;
    } else if (typeof source === 'object' && source !== null) {
      audioUrl = source.audioUrl || source.audio || source.src || '';
      title = source.title || 'Música Sem Título';
      artist = source.artist || 'Central MIDI';
      productUrl = source.productUrl || source.url || '#';
      productId = source.productId || source.id || '';
      thumbUrl = source.thumbUrl || source.thumb || '';
      card = source.card || null;
    }

    return {
      audioUrl: audioUrl,
      title: title || 'Música Sem Título',
      artist: artist || 'Central MIDI',
      productUrl: productUrl || '#',
      productId: productId,
      thumbUrl: thumbUrl,
      card: card
    };
  }

  function updatePlaylistUI() {
    if (!playlistItemsContainer) return;
    playlistItemsContainer.innerHTML = '';

    var total = playlistQueue.length;
    if (playlistCountEl) playlistCountEl.textContent = total;
    if (playlistBadgeEl) {
      playlistBadgeEl.textContent = total;
      playlistBadgeEl.style.display = total > 0 ? 'flex' : 'none';
    }

    if (total === 0) {
      playlistItemsContainer.innerHTML = '<div style="padding: 24px; text-align: center; color: #64748b; font-size: 13px;">Fila de reprodução vazia.</div>';
      return;
    }

    playlistQueue.forEach(function(track, idx) {
      var item = document.createElement('div');
      item.className = 'cm-playlist-item' + (idx === queueIndex ? ' active' : '');
      item.innerHTML = `
        <span class="cm-playlist-item-idx">${idx + 1}</span>
        <div class="cm-playlist-item-info">
          <div class="cm-playlist-item-title">${track.title}</div>
          <div class="cm-playlist-item-artist">${track.artist}</div>
        </div>
        <i class="ri-volume-up-fill cm-playlist-item-icon"></i>
      `;
      item.addEventListener('click', function() {
        playTrack(track, playlistQueue, idx);
      });
      playlistItemsContainer.appendChild(item);
    });

    var activeEl = playlistItemsContainer.querySelector('.cm-playlist-item.active');
    if (activeEl) {
      activeEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function syncBuyButton(productId, productUrl) {
    if (!playerBuyLink) return;
    if (!productId) {
      playerBuyLink.hidden = true;
      return;
    }

    playerBuyLink.hidden = false;
    playerBuyLink.setAttribute('data-product_id', productId);
    playerBuyLink.href = '?add-to-cart=' + productId;
    playerBuyLink.classList.remove('added', 'loading', 'in-cart-button');
    playerBuyLink.classList.add('add_to_cart_button', 'ajax_add_to_cart');
    playerBuyLink.innerHTML = '<i class="ri-shopping-cart-line"></i> <span>Comprar</span>';

    // Checar se já está no carrinho via centralMidiCart
    if (typeof centralMidiCart !== 'undefined' && centralMidiCart.cartProductIds) {
      var pid = parseInt(productId, 10);
      var inCart = centralMidiCart.cartProductIds.map(Number).includes(pid);
      if (inCart) {
        playerBuyLink.classList.remove('add_to_cart_button', 'ajax_add_to_cart');
        playerBuyLink.classList.add('in-cart-button');
        playerBuyLink.href = centralMidiCart.cartUrl || '#';
        playerBuyLink.innerHTML = '<i class="ri-check-line"></i> <span>' + (centralMidiCart.inCartText || 'No carrinho') + '</span>';
      }
    }
  }

  function playTrack(source, newPlaylist, playIndex) {
    if (!audioElement) return;

    var track = extractTrackData(source);
    if (!track.audioUrl) {
      showToast('⚠️ Demonstração de áudio não disponível.', 'warning');
      return;
    }

    if (newPlaylist && Array.isArray(newPlaylist)) {
      playlistQueue = newPlaylist;
      queueIndex = typeof playIndex === 'number' ? playIndex : 0;
    } else {
      var existingIdx = playlistQueue.findIndex(function(t) {
        return t.audioUrl === track.audioUrl || (t.card && t.card === track.card);
      });
      if (existingIdx !== -1) {
        queueIndex = existingIdx;
      } else {
        playlistQueue = [track];
        queueIndex = 0;
      }
    }

    if (currentCard && currentCard !== track.card) {
      setCardPlayingState(currentCard, false);
    }

    // Toggle se for a mesma faixa clicada novamente
    if (currentAudioUrl === track.audioUrl && audioElement.src) {
      if (audioElement.paused) {
        audioElement.play().catch(function() { updatePlayIcon(false); });
      } else {
        audioElement.pause();
      }
      return;
    }

    currentCard = track.card;
    currentAudioUrl = track.audioUrl;

    audioElement.pause();
    audioElement.src = track.audioUrl;
    audioElement.load();

    if (playerTitle) playerTitle.textContent = track.title;
    if (playerArtist) playerArtist.textContent = track.artist;

    if (playerCoverImg && playerCoverPlaceholder) {
      if (track.thumbUrl) {
        playerCoverImg.src = track.thumbUrl;
        playerCoverImg.style.display = 'block';
        playerCoverPlaceholder.style.display = 'none';
      } else {
        playerCoverImg.style.display = 'none';
        playerCoverPlaceholder.style.display = 'flex';
      }
    }

    syncBuyButton(track.productId, track.productUrl);

    if (playerBar) playerBar.classList.remove('hidden');
    document.body.classList.add('cm-player-active');

    updateMediaSession(track.title, track.artist);
    updatePlaylistUI();

    audioElement.play().catch(function(err) {
      console.warn('Playback error:', err);
      updatePlayIcon(false);
      setCardPlayingState(currentCard, false);
    });

    showToast('▶ ' + track.title, 'info');
  }

  function playNextTrack() {
    if (playlistQueue.length > 0 && queueIndex + 1 < playlistQueue.length) {
      queueIndex++;
      playTrack(playlistQueue[queueIndex], playlistQueue, queueIndex);
    } else if (playlistQueue.length > 0 && queueIndex + 1 >= playlistQueue.length) {
      if (audioElement) {
        audioElement.pause();
        audioElement.currentTime = 0;
      }
      setCardPlayingState(currentCard, false);
      updatePlayIcon(false);
      showToast('■ Fim da playlist', 'info');
    }
  }

  function playPrevTrack() {
    if (audioElement && audioElement.currentTime > 3) {
      audioElement.currentTime = 0;
      if (audioElement.paused) audioElement.play();
      return;
    }

    if (playlistQueue.length > 0 && queueIndex > 0) {
      queueIndex--;
      playTrack(playlistQueue[queueIndex], playlistQueue, queueIndex);
    } else if (audioElement) {
      audioElement.currentTime = 0;
      if (audioElement.paused) audioElement.play();
    }
  }

  function stopTrack() {
    if (!audioElement) return;
    audioElement.pause();
    audioElement.currentTime = 0;
    setCardPlayingState(currentCard, false);
    updatePlayIcon(false);
    if (progressFill) progressFill.style.width = '0%';
    if (currentTimeEl) currentTimeEl.textContent = '00:00';
  }

  function closePlayer() {
    stopTrack();
    if (playerBar) playerBar.classList.add('hidden');
    document.body.classList.remove('cm-player-active');
    if (playlistDrawer) playlistDrawer.style.display = 'none';
  }

  // Toast de notificação
  function showToast(message, type) {
    var existing = document.getElementById('cmidi-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'cmidi-toast';
    toast.className = 'cmidi-toast ' + (type || 'info');
    toast.innerHTML = '<span>' + message + '</span>';
    document.body.appendChild(toast);

    setTimeout(function() { toast.classList.add('show'); }, 10);
    setTimeout(function() {
      toast.classList.remove('show');
      setTimeout(function() { toast.remove(); }, 300);
    }, 3200);
  }

  // Exportação Global
  window.CentralMidiPlayer = {
    playTrack: playTrack,
    playNextTrack: playNextTrack,
    playPrevTrack: playPrevTrack,
    stopTrack: stopTrack,
    closePlayer: closePlayer,
    playPlaylist: function(playlist, startIndex) {
      if (playlist && playlist.length > 0) {
        playTrack(playlist[startIndex || 0], playlist, startIndex || 0);
      }
    }
  };

  // Compatibilidade com onclick="playAudio(...)"
  window.playAudio = function(src, containerId, btnElement) {
    playTrack(btnElement || { audioUrl: src });
  };

  // Inicialização no DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function() {
    initPlayerElements();

    // Cliques em botões de play (.cm-play-trigger)
    document.addEventListener('click', function(e) {
      var trigger = e.target.closest('.cm-play-trigger, .button-audio');
      if (!trigger) return;

      e.preventDefault();
      e.stopPropagation();

      var card = trigger.closest('.cm-track-card, .demonstracao, .product, .type-product');
      playTrack(card || trigger);
    });

    // Clique em "Reproduzir Lista" / "Reproduzir Todas" (.cm-play-monthly-playlist)
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('.cm-play-monthly-playlist');
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      var section = btn.closest('.cm-month-section') || btn.closest('.cm-archive-page') || btn.closest('section') || document;
      var cards = Array.from(section.querySelectorAll('.cm-track-card'));
      var playlist = cards.map(function(c) {
        return extractTrackData(c);
      }).filter(function(t) {
        return !!t.audioUrl;
      });

      if (playlist.length > 0) {
        playTrack(playlist[0], playlist, 0);
        showToast('🎵 Reproduzindo lista com ' + playlist.length + ' músicas', 'info');
      } else {
        showToast('⚠️ Nenhuma demonstração encontrada nesta lista.', 'warning');
      }
    });

    // Controles da barra
    if (btnMainPlay) {
      btnMainPlay.addEventListener('click', function() {
        if (!audioElement || !audioElement.src) return;
        if (audioElement.paused) {
          audioElement.play();
        } else {
          audioElement.pause();
        }
      });
    }

    if (btnStop) {
      btnStop.addEventListener('click', stopTrack);
    }

    if (btnNext) {
      btnNext.addEventListener('click', playNextTrack);
    }

    if (btnPrev) {
      btnPrev.addEventListener('click', playPrevTrack);
    }

    if (btnClose) {
      btnClose.addEventListener('click', closePlayer);
    }

    // Toggle da Gaveta de Playlist
    if (btnTogglePlaylist && playlistDrawer) {
      btnTogglePlaylist.addEventListener('click', function() {
        var isOpen = playlistDrawer.classList.toggle('is-open');
        playlistDrawer.style.display = isOpen ? 'flex' : 'none';
        if (isOpen) updatePlaylistUI();
      });
    }

    if (btnClosePlaylist && playlistDrawer) {
      btnClosePlaylist.addEventListener('click', function() {
        playlistDrawer.classList.remove('is-open');
        playlistDrawer.style.display = 'none';
      });
    }

    // Seek Timeline
    if (progressBar) {
      progressBar.addEventListener('click', function(e) {
        if (!audioElement || isNaN(audioElement.duration)) return;
        var rect = progressBar.getBoundingClientRect();
        var clickPos = (e.clientX - rect.left) / rect.width;
        audioElement.currentTime = clickPos * audioElement.duration;
      });
    }

    // Volume Slider & Persistência
    if (volumeSlider) {
      volumeSlider.addEventListener('input', function(e) {
        var val = parseFloat(e.target.value);
        if (audioElement) audioElement.volume = val;
        updateVolumeIcon(val);
        try { localStorage.setItem('cm-player-volume', String(val)); } catch (err) {}
      });
    }

    // Audio Element Event Listeners
    if (audioElement) {
      audioElement.addEventListener('play', function() {
        updatePlayIcon(true);
        if (currentCard) setCardPlayingState(currentCard, true);
      });

      audioElement.addEventListener('pause', function() {
        updatePlayIcon(false);
        if (currentCard) setCardPlayingState(currentCard, false);
      });

      audioElement.addEventListener('timeupdate', function() {
        if (!isNaN(audioElement.duration) && progressBar && progressFill) {
          var percent = (audioElement.currentTime / audioElement.duration) * 100;
          progressFill.style.width = percent + '%';
          if (currentTimeEl) currentTimeEl.textContent = formatTime(audioElement.currentTime);
          if (durationTimeEl) durationTimeEl.textContent = formatTime(audioElement.duration);
        }
      });

      audioElement.addEventListener('loadedmetadata', function() {
        if (durationTimeEl) durationTimeEl.textContent = formatTime(audioElement.duration);
      });

      audioElement.addEventListener('ended', function() {
        if (playlistQueue.length > 0 && queueIndex + 1 < playlistQueue.length) {
          playNextTrack();
        } else {
          if (currentCard) setCardPlayingState(currentCard, false);
          updatePlayIcon(false);
          if (progressFill) progressFill.style.width = '0%';
          if (currentTimeEl) currentTimeEl.textContent = '00:00';
        }
      });
    }
  });

  window.addEventListener('beforeunload', function() {
    if (audioElement) audioElement.pause();
  });
})();

/* ============================
 * Cart Validation for "Comprar" Button
 * ============================ */
(function($) {
  'use strict';

  if (typeof centralMidiCart === 'undefined') return;

  var cartIds = centralMidiCart.cartProductIds.map(Number);
  var inCartText = centralMidiCart.inCartText;
  var cartUrl = centralMidiCart.cartUrl;

  // SVG check icon for the "in cart" state
  var checkSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;flex-shrink:0"><polyline points="20 6 9 17 4 12"></polyline></svg>';

  /**
   * Convert a buy button to "in cart" state
   */
  function markAsInCart(button) {
    var $btn = $(button);
    if ($btn.hasClass('in-cart-button')) return; // Already marked

    $btn
      .removeClass('add_to_cart_button ajax_add_to_cart text_replaceable')
      .addClass('in-cart-button')
      .attr('href', cartUrl)
      .removeAttr('data-quantity')
      .removeAttr('data-product_sku')
      .removeAttr('rel');

    $btn.text(inCartText);
  }

  /**
   * On page load: mark products already in cart
   */
  function markExistingCartItems() {
    if (!cartIds.length) return;

    // Procura por todos os botões de compra (loop padrão e listagem customizada da home)
    $('a.button, button.button').each(function() {
      var $this = $(this);
      var productId = parseInt($this.attr('data-product_id'), 10);
      
      // Se não tiver data-product_id, tenta pegar do link (caso da home em cache)
      if (isNaN(productId)) {
        var href = $this.attr('href');
        if (href && href.indexOf('add-to-cart=') !== -1) {
          var match = href.match(/add-to-cart=(\d+)/);
          if (match) productId = parseInt(match[1], 10);
        }
      }

      if (!isNaN(productId) && cartIds.indexOf(productId) !== -1) {
        markAsInCart($this[0]);
      }
    });
  }

  /**
   * After AJAX add-to-cart: update the button dynamically
   */
  $(document.body).on('added_to_cart', function(e, fragments, cart_hash, $button) {
    if ($button && $button.length) {
      var productId = parseInt($button.data('product_id'), 10);

      // Mark all buttons with the same product ID (there may be duplicates)
      $('a[data-product_id="' + productId + '"]').each(function() {
        markAsInCart(this);
      });

      // Track it so page-load logic stays in sync if user scrolls
      if (cartIds.indexOf(productId) === -1) {
        cartIds.push(productId);
      }
    }
  });

  /**
   * After AJAX removal from cart: revert the button dynamically
   */
  $(document.body).on('removed_from_cart', function(e, fragments, cart_hash, $button) {
    // Quando removermos, precisamos descobrir o que foi removido.
    // O WooCommerce geralmente não passa o ID aqui, então atualizamos a lista de IDs
    // Se você tiver o ID no $button (link de remover), podemos ser específicos.
    var productId = $button ? parseInt($button.data('product_id'), 10) : null;

    if (productId) {
        // Reverte botões desse ID
        $('a.in-cart-button').each(function() {
            var $this = $(this);
            var btnId = parseInt($this.attr('data-product_id'), 10);
            
            // Se o ID bater, volta para o estado de comprar
            if (btnId === productId) {
               $this
                .removeClass('in-cart-button')
                .addClass('add_to_cart_button ajax_add_to_cart')
                .attr('href', '?add-to-cart=' + productId)
                .text('Comprar');
            }
        });

        // Tira do array interno
        var index = cartIds.indexOf(productId);
        if (index > -1) cartIds.splice(index, 1);
    }
  });

  // Run on DOM ready
  $(document).ready(function() {
    markExistingCartItems();

    // Feedback instantâneo na remoção de item do carrinho
    $(document).on('click', '.woocommerce-cart-form .product-remove a.remove', function() {
      var $row = $(this).closest('tr.cart_item');
      $row.css({
          'opacity': '0.3',
          'pointer-events': 'none',
          'filter': 'grayscale(1)',
          'transition': 'all 0.4s ease'
      });
      // Oculta levemente para dar sensação de remoção
      $row.find('td').css('border', 'none');
    });

    // =========================================================================
    // SCROLL INFINITO (CARREGAMENTO DE 20 EM 20 AO ROLAR A TELA)
    // =========================================================================
    var $infiniteGrid = $('#cm-infinite-grid');
    var $sentinel = $('#cm-infinite-sentinel');
    var $loader = $('#cm-infinite-loader');

    if ($infiniteGrid.length && $sentinel.length && typeof window.IntersectionObserver !== 'undefined') {
      $('body').addClass('cm-infinite-active');

      var taxonomy = $infiniteGrid.data('taxonomy');
      var term = $infiniteGrid.data('term');
      var currentPage = parseInt($infiniteGrid.data('current-page'), 10) || 1;
      var maxPages = parseInt($infiniteGrid.data('max-pages'), 10) || 1;
      var isLoading = false;

      function loadNextPage() {
        if (isLoading || currentPage >= maxPages) return;
        isLoading = true;
        $loader.fadeIn(200);

        var nextPage = currentPage + 1;

        $.ajax({
          url: (typeof centralMidiCart !== 'undefined' ? centralMidiCart.ajaxUrl : '/wp-admin/admin-ajax.php'),
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'cmidi_load_more_tracks',
            taxonomy: taxonomy,
            term: term,
            page: nextPage,
            nonce: (typeof centralMidiCart !== 'undefined' ? centralMidiCart.nonce : '')
          },
          success: function(response) {
            if (response && response.success && response.data && response.data.html) {
              var $newItems = $(response.data.html);
              $infiniteGrid.append($newItems);
              currentPage = nextPage;
              $infiniteGrid.data('current-page', currentPage);

              if (typeof response.data.max_pages !== 'undefined') {
                maxPages = parseInt(response.data.max_pages, 10);
              }

              // Sincroniza estado de carrinho para os novos cards carregados
              markExistingCartItems();

              if (!response.data.has_more || currentPage >= maxPages) {
                if (observer) observer.disconnect();
                $sentinel.remove();
              }
            } else {
              if (observer) observer.disconnect();
              $sentinel.remove();
            }
          },
          error: function() {
            // Em caso de erro, tenta novamente na próxima rolagem
          },
          complete: function() {
            isLoading = false;
            $loader.fadeOut(200);
          }
        });
      }

      var observer = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting && !isLoading && currentPage < maxPages) {
          loadNextPage();
        }
      }, {
        root: null,
        rootMargin: '350px 0px', // Carrega antes do usuário chegar totalmente no rodapé
        threshold: 0.1
      });

      observer.observe($sentinel[0]);
    }

    // =========================================================================
    // MODAL DE BUSCA MODERNA CENTRAL MIDI
    // =========================================================================
    var $searchOverlay = $('#cm-search-overlay');
    var $searchInput   = $('#cm-search-input');
    var $searchClose   = $('#cm-search-close');
    var $searchBackdrop= $('.cm-search-backdrop');

    var $searchClear   = $('#cm-search-clear');
    var $suggestionsBox= $('#cm-live-suggestions');
    var $quickTags     = $('#cm-search-quick-tags');
    var searchTimer    = null;
    var currentRequest = null;
    var selectedIdx    = -1;

    function openSearchModal(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      if (!$searchOverlay.length) return;

      $searchOverlay.addClass('is-active').attr('aria-hidden', 'false');
      $('body').addClass('cm-search-open').css('overflow', 'hidden');

      setTimeout(function() {
        $searchInput.focus().select();
      }, 100);
    }

    function closeSearchModal() {
      if (!$searchOverlay.length) return;
      $searchOverlay.removeClass('is-active').attr('aria-hidden', 'true');
      $('body').removeClass('cm-search-open').css('overflow', '');
    }

    function highlightTerm(text, term) {
      if (!term || !text) return text || '';
      var cleanTerm = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      return text.replace(new RegExp('(' + cleanTerm + ')', 'gi'), '<mark>$1</mark>');
    }

    function renderSkeleton() {
      var items = '';
      for (var s = 0; s < 4; s++) {
        items += '<div class="cm-skeleton-item">' +
          '<div class="cm-skeleton-icon"></div>' +
          '<div class="cm-skeleton-lines">' +
            '<div class="cm-skeleton-line"></div>' +
            '<div class="cm-skeleton-line"></div>' +
          '</div>' +
        '</div>';
      }
      return '<div class="cm-sugg-skeleton">' + items + '</div>';
    }

    function renderLiveSuggestions(data, query) {
      var catalogUrl = (typeof centralMidiCart !== 'undefined' && centralMidiCart.siteUrl) ? centralMidiCart.siteUrl + '/midis/' : '/midis/';

      if (!data || !data.results || data.results.length === 0) {
        $suggestionsBox.html(
          '<div class="cm-sugg-empty">' +
            '<div class="cm-sugg-empty-icon"><i class="ri-search-eye-line"></i></div>' +
            '<p class="cm-sugg-empty-title">Nenhum MIDI encontrado para "<strong>' + escapeHtml(query) + '</strong>"</p>' +
            '<p class="cm-sugg-empty-hint">Tente buscar pelo nome do artista, parte da letra ou gênero musical.</p>' +
            '<a href="' + catalogUrl + '" class="cm-sugg-empty-link"><i class="ri-apps-2-line"></i> Ver catálogo completo</a>' +
          '</div>'
        ).show();
        $quickTags.hide();
        return;
      }

      var html = '<div class="cm-suggestions-header">';
      html += '<span><i class="ri-music-2-line"></i> Músicas encontradas (' + data.total + ')</span>';
      html += '<span style="font-size:11px;color:#94a3b8;text-transform:none;">Use as setas ↑↓ e Enter</span>';
      html += '</div>';

      html += '<ul class="cm-suggestions-list">';
      data.results.forEach(function(item, idx) {
        var highlightedTitle = highlightTerm(item.title, query);
        var highlightedArtist = highlightTerm(item.artist, query);
        html += '<li class="cm-suggestion-item" data-index="' + idx + '">';
        html += '<a href="' + item.url + '" class="cm-suggestion-link">';
        html += '<div class="cm-sugg-icon"><i class="ri-play-circle-line"></i></div>';
        html += '<div class="cm-sugg-info">';
        html += '<div class="cm-sugg-title">' + highlightedTitle + '</div>';
        html += '<div class="cm-sugg-meta">';
        if (item.artist) {
          html += '<span class="cm-sugg-artist">' + highlightedArtist + '</span>';
        }
        if (item.genre) {
          html += '<span class="cm-sugg-genre">' + item.genre + '</span>';
        }
        html += '</div>';
        html += '</div>';
        if (item.price_html) {
          html += '<div class="cm-sugg-price">' + item.price_html + '</div>';
        }
        html += '<i class="ri-arrow-right-s-line" style="color:#64748b;font-size:18px;"></i>';
        html += '</a>';
        html += '</li>';
      });
      html += '</ul>';

      if (data.total > data.results.length) {
        html += '<a href="' + data.search_url + '" class="cm-sugg-view-all">';
        html += '<span>Ver todos os ' + data.total + ' resultados para "' + escapeHtml(query) + '"</span>';
        html += '<i class="ri-arrow-right-line"></i>';
        html += '</a>';
      }

      $suggestionsBox.html(html).show();
      $quickTags.hide();
      selectedIdx = -1;
    }

    function escapeHtml(string) {
      return String(string)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function doLiveSearch(query) {
      if (currentRequest) {
        currentRequest.abort();
      }

      // Mostra skeleton animado enquanto aguarda o AJAX
      $suggestionsBox.html(renderSkeleton()).show();
      $quickTags.hide();

      var ajaxUrl = (typeof centralMidiCart !== 'undefined' && centralMidiCart.ajaxUrl) ? centralMidiCart.ajaxUrl : '/wp-admin/admin-ajax.php';

      currentRequest = $.ajax({
        url: ajaxUrl,
        type: 'GET',
        dataType: 'json',
        data: {
          action: 'cmidi_ajax_live_search',
          query: query
        },
        success: function(res) {
          if (res && res.success && res.data) {
            renderLiveSuggestions(res.data, query);
          }
        },
        error: function(xhr, status) {
          if (status !== 'abort') {
            $suggestionsBox.hide();
            $quickTags.show();
          }
        }
      });
    }

    // Monitora digitação no campo de busca com debounce
    $searchInput.on('input', function() {
      var val = $(this).val().trim();

      if (val.length > 0) {
        $searchClear.show();
      } else {
        $searchClear.hide();
      }

      clearTimeout(searchTimer);

      if (val.length < 2) {
        $suggestionsBox.hide().empty();
        $quickTags.show();
        if (currentRequest) currentRequest.abort();
        return;
      }

      searchTimer = setTimeout(function() {
        doLiveSearch(val);
      }, 260); // 260ms debounce para alta fluidez sem sobrecarga
    });

    // Botão Limpar busca
    $searchClear.on('click', function() {
      $searchInput.val('').focus();
      $(this).hide();
      $suggestionsBox.hide().empty();
      $quickTags.show();
      if (currentRequest) currentRequest.abort();
    });

    // Navegação por teclado nas sugestões (Seta para cima / para baixo / Enter)
    $searchInput.on('keydown', function(e) {
      var $items = $suggestionsBox.find('.cm-suggestion-item');
      if (!$items.length || !$suggestionsBox.is(':visible')) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIdx = (selectedIdx + 1) >= $items.length ? 0 : (selectedIdx + 1);
        $items.removeClass('is-selected');
        $items.eq(selectedIdx).addClass('is-selected');
        $items.eq(selectedIdx)[0].scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIdx = (selectedIdx - 1) < 0 ? ($items.length - 1) : (selectedIdx - 1);
        $items.removeClass('is-selected');
        $items.eq(selectedIdx).addClass('is-selected');
        $items.eq(selectedIdx)[0].scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'Enter' && selectedIdx >= 0) {
        e.preventDefault();
        var targetUrl = $items.eq(selectedIdx).find('a').attr('href');
        if (targetUrl) {
          window.location.href = targetUrl;
        }
      }
    });

    // Intercepta todos os cliques no botão de lupa do header (Header Customizado e legados)
    $(document).on('click', '#cm-header-search-btn, .cm-search-trigger-btn, .header-search-lightbox a, a[href="#search-lightbox"], .header-search a', function(e) {
      openSearchModal(e);
      return false;
    });

    // Fechar ao clicar no botão X ou no backdrop
    $searchClose.on('click', closeSearchModal);
    $searchBackdrop.on('click', closeSearchModal);

    // Fechar ao pressionar ESC ou abrir com tecla de atalho '/'
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape' && $searchOverlay.hasClass('is-active')) {
        closeSearchModal();
      } else if (e.key === '/' && !$searchOverlay.hasClass('is-active') && !$('input, textarea, select').is(':focus')) {
        openSearchModal(e);
      }
    });

    // ========================================================================
    // ENVIO AJAX DO FORMULÁRIO DE CONTATO NATIVO
    // ========================================================================
    $(document).on('submit', '#cm-native-contact-form', function(e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('#cm-contact-submit');
      var $resp = $form.find('#cm-form-response');
      var originalBtnHtml = $btn.html();

      $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Enviando...');
      $resp.hide().removeClass('cm-form-success cm-form-error').text('');

      var formData = {
        action: 'cmidi_submit_contact',
        nonce: $form.find('input[name="cmidi_contact_nonce"]').val(),
        hp_field: $form.find('input[name="cmidi_hp_website"]').val(),
        nome: $form.find('input[name="nome"]').val(),
        email: $form.find('input[name="email"]').val(),
        telefone: $form.find('input[name="telefone"]').val(),
        mensagem: $form.find('textarea[name="mensagem"]').val()
      };

      var ajaxUrl = (typeof centralMidiCart !== 'undefined' && centralMidiCart.ajaxUrl) ? centralMidiCart.ajaxUrl : '/wp-admin/admin-ajax.php';

      $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: formData,
        dataType: 'json'
      }).done(function(res) {
        if (res.success) {
          $resp.addClass('cm-form-success').text(res.data.message || 'Mensagem enviada com sucesso!').fadeIn();
          $form[0].reset();
        } else {
          $resp.addClass('cm-form-error').text((res.data && res.data.message) ? res.data.message : 'Erro ao enviar mensagem.').fadeIn();
        }
      }).fail(function() {
        $resp.addClass('cm-form-error').text('Ocorreu uma instabilidade na conexão. Tente novamente ou use o WhatsApp.').fadeIn();
      }).always(function() {
        $btn.prop('disabled', false).html(originalBtnHtml);
      });
    });
  });

  /* ------------------------------------------------------------------
     Voltar ao topo
     ------------------------------------------------------------------
     O Flatsome registrava o clique em jQuery("#top-link").on("click",
     jQuery.scrollTo(0)) dentro de flatsome.js. Como o footer agora é
     renderizado pelo footer.php do child, o botão tem comportamento
     próprio e não depende mais do JS do tema pai.
     Só age se o botão existir, e é idempotente.
     ------------------------------------------------------------------ */
  (function() {
    var btt = document.getElementById('top-link');
    if (!btt || btt.dataset.cmBttBound === '1') return;
    btt.dataset.cmBttBound = '1';
    btt.addEventListener('click', function(ev) {
      ev.preventDefault();
      var reduce = window.matchMedia &&
                   window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({top: 0, behavior: reduce ? 'auto' : 'smooth'});
    });
  })();

  /* ------------------------------------------------------------------
     Alternador de Tema (Light / Dark) com persistência em localStorage e detecção de OS
     ------------------------------------------------------------------ */
  (function() {
    var STORAGE_KEY = 'cmidi_user_theme';

    function getSystemTheme() {
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
        return 'light';
      }
      return 'dark';
    }

    function getPreferredTheme() {
      try {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved === 'light' || saved === 'dark') return saved;
      } catch (e) {}

      // Se ainda não existir valor salvo no localStorage, detecta o tema do PC / Sistema Operacional
      return getSystemTheme();
    }

    function applyTheme(theme) {
      if (theme === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
        document.body.classList.add('theme-light');
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.body.classList.remove('theme-light');
      }
    }

    // Aplicação imediata inicial
    applyTheme(getPreferredTheme());

    // Sincroniza se o usuário mudar o tema do PC/Sistema em tempo real (apenas se ele ainda não escolheu manualmente)
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function(e) {
        try {
          var saved = localStorage.getItem(STORAGE_KEY);
          if (!saved) {
            applyTheme(e.matches ? 'light' : 'dark');
          }
        } catch (err) {}
      });
    }

    // Event listener delegado no botão de alternar tema (desktop e mobile)
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('#cmidi-theme-toggle-btn, #cm-mobile-theme-btn');
      if (!btn) return;
      e.preventDefault();

      var current = document.documentElement.getAttribute('data-theme') || 'dark';
      var nextTheme = (current === 'dark') ? 'light' : 'dark';

      applyTheme(nextTheme);
      try {
        localStorage.setItem(STORAGE_KEY, nextTheme);
      } catch (err) {}
    });
  })();

  /* ------------------------------------------------------------------
     Header Customizado Central MIDI: Menu Mobile Fullscreen, Busca & Modais
     ------------------------------------------------------------------ */
  (function() {
    // 1. Menu Mobile Fullscreen Toggle
    var mobileMenu = document.getElementById('cm-mobile-fullscreen-menu');
    var openBtn = document.getElementById('cm-mobile-menu-toggle');
    var closeBtn = document.getElementById('cm-mobile-menu-close');
    var backdrop = mobileMenu ? mobileMenu.querySelector('.cm-mobile-fullscreen-backdrop') : null;

    function openMobileMenu() {
      if (!mobileMenu) return;
      mobileMenu.classList.add('is-active');
      mobileMenu.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    }

    function closeMobileMenu() {
      if (!mobileMenu) return;
      mobileMenu.classList.remove('is-active');
      mobileMenu.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
    }

    if (openBtn) {
      openBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openMobileMenu();
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeMobileMenu();
      });
    }

    if (backdrop) {
      backdrop.addEventListener('click', function() {
        closeMobileMenu();
      });
    }

    // Fechar ao teclar ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' || e.keyCode === 27) {
        closeMobileMenu();
        closeNewsletterModal();
        closeSearchDrawer();
      }
    });

    // 2. Acordeão de Sub-menu no Mobile
    var accordionToggles = document.querySelectorAll('.cm-mobile-accordion-toggle');
    accordionToggles.forEach(function(toggle) {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        var isExpanded = this.getAttribute('aria-expanded') === 'true';
        var subList = this.nextElementSibling;
        
        if (isExpanded) {
          this.setAttribute('aria-expanded', 'false');
          if (subList) subList.classList.remove('is-open');
        } else {
          this.setAttribute('aria-expanded', 'true');
          if (subList) subList.classList.add('is-open');
        }
      });
    });

    // 3. Busca Rápida Desktop -> Abre a Modal de Busca Moderna (#cm-search-overlay)
    var searchBtn = document.getElementById('cm-header-search-btn');
    if (searchBtn) {
      searchBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (typeof openSearchModal === 'function') {
          openSearchModal(e);
        }
      });
    }

    // 4. Modal Newsletter Nativo
    var newsletterModal = document.getElementById('cm-newsletter-modal');
    var openNewsletterBtn = document.getElementById('cm-open-newsletter-btn');
    var closeNewsletterBtn = document.getElementById('cm-close-newsletter-modal');

    function openNewsletterModal() {
      if (!newsletterModal) return;
      newsletterModal.style.display = 'flex';
      newsletterModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeNewsletterModal() {
      if (!newsletterModal) return;
      newsletterModal.style.display = 'none';
      newsletterModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    if (openNewsletterBtn) {
      openNewsletterBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openNewsletterModal();
      });
    }

    if (closeNewsletterBtn) {
      closeNewsletterBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeNewsletterModal();
      });
    }

    if (newsletterModal) {
      newsletterModal.addEventListener('click', function(e) {
        if (e.target === newsletterModal) {
          closeNewsletterModal();
        }
      });
    }

    // 6. Sticky Header ao rolar a página (Fixa a 2ª linha no topo)
    var topBar = document.getElementById('cm-top-bar');
    var mainHeader = document.getElementById('cm-main-header');
    var headerWrapper = document.getElementById('cm-main-header-wrapper');

    if (mainHeader && headerWrapper) {
      var isHeaderStuck = false;

      function updateStickyHeader() {
        // Altura do Top Bar ou offset de ativação
        var threshold = 0;
        if (topBar) {
          threshold = topBar.offsetTop + topBar.offsetHeight;
        } else {
          threshold = headerWrapper.offsetTop;
        }

        var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;

        if (scrollY > threshold) {
          if (!isHeaderStuck) {
            isHeaderStuck = true;
            // Preserva a altura no container pai para não dar pulo no conteúdo
            var headerHeight = mainHeader.offsetHeight;
            headerWrapper.style.minHeight = headerHeight + 'px';
            mainHeader.classList.add('cm-is-fixed');
          }
        } else {
          if (isHeaderStuck) {
            isHeaderStuck = false;
            headerWrapper.style.minHeight = '';
            mainHeader.classList.remove('cm-is-fixed');
          }
        }
      }

      window.addEventListener('scroll', updateStickyHeader, { passive: true });
      window.addEventListener('resize', function() {
        if (isHeaderStuck && mainHeader) {
          var h = mainHeader.offsetHeight;
          headerWrapper.style.minHeight = h + 'px';
        }
      });
      // Executa checagem inicial caso a página recarregue já rolada
      updateStickyHeader();
    }

    // 7. Dropdown desktop — aria-expanded dinâmico ao hover/focus
    var dropdownItems = document.querySelectorAll('.cm-has-dropdown');
    dropdownItems.forEach(function(item) {
      var toggle = item.querySelector('.cm-dropdown-toggle');
      if (!toggle) return;

      // Inicializa como fechado
      toggle.setAttribute('aria-expanded', 'false');

      item.addEventListener('mouseenter', function() {
        toggle.setAttribute('aria-expanded', 'true');
      });
      item.addEventListener('mouseleave', function() {
        toggle.setAttribute('aria-expanded', 'false');
      });
      toggle.addEventListener('focus', function() {
        toggle.setAttribute('aria-expanded', 'true');
      });
      item.addEventListener('focusout', function(e) {
        // Fecha somente se o foco saiu completamente do item
        if (!item.contains(e.relatedTarget)) {
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    });
  })();

  /* ------------------------------------------------------------------
     Badge do Carrinho Reativo (WooCommerce fragments)
     Atualiza #cm-cart-contents-count e #cm-cart-contents-total
     sem precisar recarregar a página.
     ------------------------------------------------------------------ */
  (function() {
    function updateCartBadge() {
      var $count = $('#cm-cart-contents-count');
      var $total = $('#cm-cart-contents-total');
      var $wcCount = $('.cart-contents-count').first();
      var $wcTotal = $('.header-cart-link .amount').first();

      // Tenta ler do fragmento de carrinho do WooCommerce
      if ($wcCount.length && $count.length) {
        var newCount = $wcCount.text().trim();
        $count.text(newCount);
        if (parseInt(newCount, 10) === 0) {
          $count.hide();
        } else {
          $count.show();
        }
      }

      if ($wcTotal.length && $total.length) {
        $total.html($wcTotal.closest('.cart-contents').find('.woocommerce-Price-amount').first().parent().html() || $wcTotal.parent().html());
      }
    }

    // Evento disparado pelo WooCommerce após adicionar ao carrinho (fragments updated)
    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart removed_from_cart', function() {
      updateCartBadge();
    });
  })();

})(jQuery);

