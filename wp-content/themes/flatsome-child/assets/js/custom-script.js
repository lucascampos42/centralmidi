var sound = null;
var activeTimeline = null;
var isPlaying = false;
var currentSrc = null;
var currentBtn = null;
var currentTitle = null;
var currentProductId = null;
var currentPlaylist = [];
var currentPlaylistIndex = -1;

function disposeSound() {
  if (sound) {
    try {
      sound.unload();
    } catch(e) {}
    sound = null;
  }
  if (activeTimeline) {
    activeTimeline.stopTracking();
    activeTimeline = null;
  }
  isPlaying = false;
  updateGlobalPlayerProgress(0, 0);
}

function playAudio(src, containerId, btnElement) {
  var container = null;
  if (containerId) {
    container = document.getElementById(containerId);
  }
  
  // Capturar título, artista e thumbnail
  currentTitle = 'Demo';
  var currentArtist = '';
  var currentThumb = '';
  currentProductId = null;

  if (btnElement) {
    var card = btnElement.closest('.fp-card, .product, .type-product, .col');
    if (card) {
      // Título
      var titleEl = card.querySelector('.fp-title, .woocommerce-loop-product__title, .product-title, h2, h3, h4, a[href*="/product/"]');
      if (titleEl) currentTitle = titleEl.textContent.trim();
      
      // Artista
      var artistEl = card.querySelector('.fp-artist');
      if (artistEl) currentArtist = artistEl.textContent.trim();

      // Thumbnail
      var thumbEl = card.querySelector('.fp-img img, .attachment-woocommerce_thumbnail');
      if (thumbEl) currentThumb = thumbEl.src;

      currentProductId = card.getAttribute('data-product-id');
      
      // Se for um card da home, o containerId pode ser nulo agora que removemos a timeline individual
      if (!containerId && card.querySelector('.player-container')) {
        containerId = card.querySelector('.player-container').id;
      }
    }
  }

  console.log('PlayAudio:', { src, currentTitle, currentArtist, currentProductId });
  
  if (sound && (isPlaying || currentSrc === src)) {
    if (currentSrc === src) {
      sound.pause();
      return;
    } else {
      disposeSound();
    }
  } else if (sound && !isPlaying && currentSrc === src) {
    sound.play();
    return;
  }
  
  currentSrc = src;
  currentBtn = btnElement;
  
  // Visual de carregando
  if (btnElement) {
    btnElement.classList.add('loading');
    btnElement.style.opacity = '0.5';
  }
  
  // Mostrar e atualizar player global
  var globalPlayer = document.getElementById('cmidi-global-player');
  if (globalPlayer) {
    globalPlayer.style.display = 'flex'; // Mudado de block para flex para respeitar o CSS
    globalPlayer.querySelector('.player-track-title').textContent = currentTitle;
    
    var artistDisplay = globalPlayer.querySelector('.player-track-artist');
    if (artistDisplay) artistDisplay.textContent = currentArtist;

    var thumbImg = document.getElementById('player-thumb-img');
    var thumbPlaceholder = globalPlayer.querySelector('.player-thumb-placeholder');
    if (thumbImg) {
      if (currentThumb) {
        thumbImg.src = currentThumb;
        thumbImg.style.display = 'block';
        if (thumbPlaceholder) thumbPlaceholder.style.display = 'none';
      } else {
        thumbImg.style.display = 'none';
        if (thumbPlaceholder) thumbPlaceholder.style.display = 'flex';
      }
    }

    var buyLink = document.getElementById('player-buy-link');
    if (buyLink && currentProductId) {
      buyLink.href = '?add-to-cart=' + currentProductId;
      buyLink.setAttribute('data-product_id', currentProductId);
      
      // Resetar estado do botão
      buyLink.textContent = 'Comprar';
      buyLink.classList.remove('added', 'loading', 'in-cart-button');
      buyLink.classList.add('add_to_cart_button', 'ajax_add_to_cart');

      // Verificar se o produto já está no carrinho
      if (typeof centralMidiCart !== 'undefined' && centralMidiCart.cartProductIds) {
        var pid = parseInt(currentProductId);
        if (centralMidiCart.cartProductIds.map(Number).includes(pid)) {
          buyLink.classList.remove('add_to_cart_button', 'ajax_add_to_cart');
          buyLink.classList.add('in-cart-button');
          buyLink.textContent = centralMidiCart.inCartText || 'No carrinho';
          buyLink.href = centralMidiCart.cartUrl || '#';
        }
      }
    }
    // Sempre criar uma timeline para o player global
    activeTimeline = new PlayerTimeline(globalPlayer, null);
    
    // Atualizar visual da playlist se o painel estiver aberto
    updatePlaylistUI();
  }

  // Se houver um container local (ex: página do produto), sobrepõe
  if (container) {
    activeTimeline = new PlayerTimeline(container, null);
  }
  
  sound = new Howl({
    src: [src],
    html5: true,
    onload: function() {
      if (activeTimeline) {
        activeTimeline.setSound(sound);
        activeTimeline.setDuration(sound.duration() || 59);
      }
      if (btnElement) {
        btnElement.classList.remove('loading');
        btnElement.style.opacity = '1';
      }
      var totalTimeEl = document.querySelector('.player-total-time');
      if (totalTimeEl) totalTimeEl.textContent = formatTime(sound.duration());
    },
    onloaderror: function(id, error) {
      console.log('Audio load error:', error);
      if (btnElement) {
        btnElement.classList.remove('loading');
        btnElement.style.opacity = '1';
      }
    },
    onplay: function() {
      isPlaying = true;
      updatePlayButton(btnElement, true);
      updateGlobalPlayerPlayPause(true);
      if (activeTimeline) {
        activeTimeline.startTracking();
      }
      showToast('▶ Reproduzindo: ' + (currentTitle || 'Demo'), 'success');
    },
    onend: function() {
      isPlaying = false;
      updatePlayButton(btnElement, false);
      updateGlobalPlayerPlayPause(false);
      if (activeTimeline) {
        activeTimeline.stopTracking();
        activeTimeline.reset();
      }
      
      // Lógica de playlist (Próxima música)
      if (currentPlaylist.length > 0 && currentPlaylistIndex < currentPlaylist.length - 1) {
        currentPlaylistIndex++;
        var nextTrack = currentPlaylist[currentPlaylistIndex];
        playAudio(nextTrack.src, nextTrack.containerId, nextTrack.btn);
      } else {
        showToast('■ Reprodução finalizada', 'info');
      }
    },
    onpause: function() {
      isPlaying = false;
      updatePlayButton(btnElement, false);
      updateGlobalPlayerPlayPause(false);
      if (activeTimeline) {
        activeTimeline.stopTracking();
      }
    },
    onstop: function() {
      isPlaying = false;
      updatePlayButton(btnElement, false);
      updateGlobalPlayerPlayPause(false);
      if (activeTimeline) {
        activeTimeline.stopTracking();
      }
    }
  });
  sound.play();
}

function updatePlayButton(btn, playing) {
  if (!btn) return;
  var svg = btn.querySelector('svg');
  var textSpan = btn.querySelector('.btn-text');
  if (!svg) return;
  
  if (playing) {
    // Ícone de Pause (duas barras verticais)
    svg.innerHTML = '<path d="M6 4h4v12H6V4zm8 0h4v12h-4V4z"/>';
    svg.setAttribute('viewBox', '0 0 24 24');
    if (textSpan) textSpan.textContent = 'STOP';
    btn.classList.add('playing');
  } else {
    // Ícone de Play (triângulo)
    svg.innerHTML = '<polygon points="6,4 14,10 6,16"/>';
    svg.setAttribute('viewBox', '0 0 20 20');
    if (textSpan) textSpan.textContent = 'PLAY';
    btn.classList.remove('playing');
  }
}

// Funções para o Player Global
function updateGlobalPlayerPlayPause(playing) {
  var btn = document.getElementById('player-play-pause');
  if (!btn) return;
  var svg = btn.querySelector('svg');
  if (playing) {
    svg.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
  } else {
    svg.innerHTML = '<path d="M8 5v14l11-7z"/>';
  }
}

function updateGlobalPlayerProgress(current, duration) {
  var fill = document.querySelector('.cmidi-global-player .player-progress-fill');
  var currentTimeEl = document.querySelector('.player-current-time');
  if (fill) {
    var percent = duration > 0 ? (current / duration) * 100 : 0;
    fill.style.width = percent + '%';
  }
  if (currentTimeEl) {
    currentTimeEl.textContent = formatTime(current);
  }
}

function formatTime(seconds) {
  var min = Math.floor(seconds / 60);
  var sec = Math.floor(seconds % 60);
  return min + ':' + (sec < 10 ? '0' : '') + sec;
}

// Eventos do Player Global
document.addEventListener('DOMContentLoaded', function() {
  var playPauseBtn = document.getElementById('player-play-pause');
  if (playPauseBtn) {
    playPauseBtn.addEventListener('click', function() {
      if (sound) {
        if (isPlaying) sound.pause();
        else sound.play();
      }
    });
  }

  // Toggle Playlist Panel
  var togglePlaylistBtn = document.getElementById('player-toggle-playlist');
  var playlistPanel = document.getElementById('player-playlist-panel');
  var closePlaylistBtn = document.getElementById('close-playlist');

  if (togglePlaylistBtn && playlistPanel) {
    togglePlaylistBtn.addEventListener('click', function() {
      var isVisible = playlistPanel.style.display === 'flex';
      playlistPanel.style.display = isVisible ? 'none' : 'flex';
      if (!isVisible) updatePlaylistUI();
    });
  }

  if (closePlaylistBtn && playlistPanel) {
    closePlaylistBtn.addEventListener('click', function() {
      playlistPanel.style.display = 'none';
    });
  }

  var nextBtn = document.getElementById('player-next');
  if (nextBtn) {
    nextBtn.addEventListener('click', function() {
      if (currentPlaylist.length > 0 && currentPlaylistIndex < currentPlaylist.length - 1) {
        currentPlaylistIndex++;
        var nextTrack = currentPlaylist[currentPlaylistIndex];
        playAudio(nextTrack.src, nextTrack.containerId, nextTrack.btn);
      }
    });
  }

  var prevBtn = document.getElementById('player-prev');
  if (prevBtn) {
    prevBtn.addEventListener('click', function() {
      if (currentPlaylist.length > 0 && currentPlaylistIndex > 0) {
        currentPlaylistIndex--;
        var prevTrack = currentPlaylist[currentPlaylistIndex];
        playAudio(prevTrack.src, prevTrack.containerId, prevTrack.btn);
      }
    });
  }

  // Click na barra de progresso global
  var progressBar = document.querySelector('.player-progress-bar');
  if (progressBar) {
    progressBar.addEventListener('click', function(e) {
      if (sound) {
        var rect = progressBar.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var width = rect.width;
        var percent = x / width;
        sound.seek(sound.duration() * percent);
      }
    });
  }
});

// Função para "Ouvir Tudo"
function playAllInSection(btn) {
  var section = btn.closest('.fp-section');
  if (!section) return;

  var cards = section.querySelectorAll('.fp-card[data-audio]');
  currentPlaylist = [];
  cards.forEach(function(card) {
    var src = card.getAttribute('data-audio');
    if (src && src !== 'null' && src !== '') {
      var title = 'Demo';
      var titleEl = card.querySelector('.fp-title');
      if (titleEl) title = titleEl.textContent.trim();
      
      currentPlaylist.push({
        src: src,
        title: title,
        productId: card.getAttribute('data-product-id'),
        containerId: null,
        btn: card.querySelector('.fp-play-btn')
      });
    }
  });

  if (currentPlaylist.length > 0) {
    currentPlaylistIndex = 0;
    var firstTrack = currentPlaylist[0];
    playAudio(firstTrack.src, firstTrack.containerId, firstTrack.btn);
    showToast('🎵 Iniciando playlist do mês', 'info');
    updatePlaylistUI();
  }
}

function updatePlaylistUI() {
  var container = document.getElementById('playlist-items');
  if (!container) return;

  container.innerHTML = '';
  
  if (currentPlaylist.length === 0) {
    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #9ca3af; font-size: 12px;">Fila vazia</div>';
    return;
  }

  currentPlaylist.forEach(function(track, index) {
    var item = document.createElement('div');
    item.className = 'playlist-item' + (index === currentPlaylistIndex ? ' active' : '');
    item.innerHTML = `
      <span class="playlist-item-playing-icon">▶</span>
      <span class="playlist-item-title">${track.title}</span>
    `;
    item.onclick = function() {
      currentPlaylistIndex = index;
      playAudio(track.src, track.containerId, track.btn);
    };
    container.appendChild(item);
  });
  
  // Scroll para o item ativo
  var activeItem = container.querySelector('.playlist-item.active');
  if (activeItem) {
    activeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
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
  
  setTimeout(function() {
    toast.classList.add('show');
  }, 10);
  
  setTimeout(function() {
    toast.classList.remove('show');
    setTimeout(function() { toast.remove(); }, 300);
  }, 3000);
}

window.addEventListener('beforeunload', disposeSound);
window.addEventListener('pagehide', disposeSound);

class PlayerTimeline {
  constructor(container, sound) {
    this.container = container;
    this.sound = sound;
    this.interval = null;
    this.duration = 59;
    this.progressFill = this.container.querySelector('.player-progress-fill');
  }
  
  setSound(sound) {
    this.sound = sound;
  }

  setDuration(seconds) {
    this.duration = seconds;
  }

  updateTimeDisplay(current, total) {
    if (!total || total <= 0) {
      total = this.sound ? this.sound.duration() : 59;
    }
    var percent = total > 0 ? (current / total) * 100 : 0;
    
    // Atualizar o container local (se houver)
    if (this.progressFill) {
      this.progressFill.style.width = percent + '%';
    }
    
    // SEMPRE atualizar o player global se esta for a trilha ativa
    if (sound) {
      updateGlobalPlayerProgress(current, total);
    }
  }

  startTracking() {
    this.stopTracking();
    var self = this;
    this.lastUpdate = 0;
    var animate = function(timestamp) {
      if (!self.sound || !isPlaying) {
        self.stopTracking();
        return;
      }
      if (timestamp - self.lastUpdate > 200) { // Reduzido de 100 para 200ms para poupar CPU mobile
        var current = self.sound.seek();
        var duration = self.sound.duration() || self.duration;
        self.updateTimeDisplay(current, duration);
        self.lastUpdate = timestamp;
      }
      self.interval = requestAnimationFrame(animate);
    };
    this.interval = requestAnimationFrame(animate);
  }

  stopTracking() {
    if (this.interval) {
      if (typeof this.interval === 'number') {
        cancelAnimationFrame(this.interval);
      } else {
        clearInterval(this.interval);
      }
      this.interval = null;
    }
  }

  reset() {
    this.stopTracking();
    this.updateTimeDisplay(0, this.duration);
  }
}

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
  });

})(jQuery);