/**
 * Central MIDI - Live Search & Autocomplete Suggestions
 */
document.addEventListener('DOMContentLoaded', () => {
    const searchInputs = document.querySelectorAll('#cm-search-input, #cm-search-input-mobile');
    if (!searchInputs.length) return;

    const ajaxUrl = (window.centralMidiData && window.centralMidiData.ajaxUrl) ? window.centralMidiData.ajaxUrl : '/wp-admin/admin-ajax.php';

    searchInputs.forEach((input) => {
        const form = input.closest('form');
        if (!form) return;

        // Container for dropdown
        let dropdown = form.querySelector('.cm-search-suggestions');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'cm-search-suggestions';
            form.appendChild(dropdown);
        }

        let debounceTimer = null;
        let currentAbort = null;
        let activeIndex = -1;

        function escapeHTML(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function highlightMatch(text, query) {
            if (!text || !query) return escapeHTML(text);
            const safeText = escapeHTML(text);
            const safeQuery = escapeHTML(query).trim();
            if (!safeQuery) return safeText;
            const regex = new RegExp(`(${safeQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return safeText.replace(regex, '<mark class="cm-search-hl">$1</mark>');
        }

        function closeDropdown() {
            dropdown.classList.remove('is-open');
            dropdown.innerHTML = '';
            activeIndex = -1;
        }

        function renderResults(data, query) {
            const tracks = data.tracks || [];
            const artists = data.artists || [];

            if (!tracks.length && !artists.length) {
                dropdown.innerHTML = `
                    <div class="cm-search-empty">
                        <i class="ri-search-eye-line"></i>
                        <span>Nenhum resultado encontrado para "<strong>${escapeHTML(query)}</strong>"</span>
                        <small>Pressione Enter para ver resultados no catálogo</small>
                    </div>
                `;
                dropdown.classList.add('is-open');
                return;
            }

            let html = '';

            // Section: Artists
            if (artists.length) {
                html += `
                    <div class="cm-suggest-section">
                        <span class="cm-suggest-title"><i class="ri-user-voice-line"></i> Artistas</span>
                        <div class="cm-suggest-artists-list">
                            ${artists.map(art => `
                                <a href="${escapeHTML(art.url)}" class="cm-suggest-artist-pill cm-suggest-item">
                                    <i class="ri-user-star-line"></i>
                                    <span>${highlightMatch(art.name, query)}</span>
                                    <small>(${art.count} ${art.count === 1 ? 'MIDI' : 'MIDIs'})</small>
                                </a>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            // Section: MIDI Tracks
            if (tracks.length) {
                html += `
                    <div class="cm-suggest-section">
                        <span class="cm-suggest-title"><i class="ri-disc-line"></i> Músicas & Playbacks</span>
                        <div class="cm-suggest-tracks-list">
                            ${tracks.map(track => {
                                const classLower = String(track.classificacao || 'M').toLowerCase();
                                return `
                                    <div class="cm-suggest-track cm-suggest-item" data-url="${escapeHTML(track.url)}">
                                        <div class="cm-suggest-track-left">
                                            ${track.demo_audio ? `
                                                <button type="button" 
                                                        class="cm-suggest-play-btn cm-play-trigger"
                                                        data-audio="${escapeHTML(track.demo_audio)}"
                                                        data-title="${escapeHTML(track.title)}"
                                                        data-artist="${escapeHTML(track.artista)}"
                                                        data-url="${escapeHTML(track.url)}"
                                                        title="Ouvir Demonstração MP3"
                                                        aria-label="Ouvir demonstração de ${escapeHTML(track.title)}">
                                                    <i class="ri-play-fill"></i>
                                                </button>
                                            ` : `
                                                <span class="cm-suggest-disc-icon"><i class="ri-music-2-fill"></i></span>
                                            `}
                                            <div class="cm-suggest-track-info">
                                                <a href="${escapeHTML(track.url)}" class="cm-suggest-track-title">
                                                    ${highlightMatch(track.title, query)}
                                                </a>
                                                <div class="cm-suggest-track-meta">
                                                    <span class="cm-suggest-artist">${highlightMatch(track.artista, query)}</span>
                                                    ${track.genero ? `<span class="cm-suggest-dot">•</span><span class="cm-suggest-genre">${escapeHTML(track.genero)}</span>` : ''}
                                                    <span class="centralmidi-badge-class class-${classLower}" style="position: static; font-size: 0.65rem; padding: 2px 6px; box-shadow: none; vertical-align: middle; margin-left: 4px;" title="#${escapeHTML(track.classificacao)}: ${escapeHTML(track.class_label)}">
                                                        #${escapeHTML(track.classificacao)}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="cm-suggest-track-right">
                                            <span class="cm-suggest-price">${track.price_html}</span>
                                            <a href="${escapeHTML(track.url)}" class="cm-suggest-link-arrow" title="Ver detalhes">
                                                <i class="ri-arrow-right-s-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            }

            // Footer of dropdown
            html += `
                <div class="cm-suggest-footer">
                    <a href="${escapeHTML(data.allUrl)}" class="cm-suggest-view-all">
                        <span>Ver todos os resultados para "<strong>${escapeHTML(query)}</strong>"</span>
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            `;

            dropdown.innerHTML = html;
            dropdown.classList.add('is-open');
            activeIndex = -1;

            // Wire audio play button triggers inside dropdown
            dropdown.querySelectorAll('.cm-play-trigger').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    e.preventDefault();
                    if (window.CentralMidiPlayer && typeof window.CentralMidiPlayer.playFromElement === 'function') {
                        window.CentralMidiPlayer.playFromElement(btn);
                    } else {
                        // Fallback click simulate
                        const trackEl = btn.closest('.cm-suggest-track');
                        if (trackEl && trackEl.dataset.audio) {
                            const audioEl = document.getElementById('cm-audio-element');
                            if (audioEl) {
                                audioEl.src = btn.dataset.audio;
                                audioEl.play().catch(() => {});
                            }
                        }
                    }
                });
            });

            // Make clicking track row navigate to URL (except when clicking play button)
            dropdown.querySelectorAll('.cm-suggest-track').forEach(row => {
                row.addEventListener('click', (e) => {
                    if (e.target.closest('.cm-play-trigger')) return;
                    if (row.dataset.url) {
                        window.location.href = row.dataset.url;
                    }
                });
            });
        }

        function performSearch(query) {
            if (currentAbort) {
                currentAbort.abort();
            }

            const trimmed = query.trim();
            if (trimmed.length < 2) {
                closeDropdown();
                return;
            }

            // Show loading placeholder
            dropdown.innerHTML = `
                <div class="cm-search-loading">
                    <i class="ri-loader-4-line ri-spin"></i>
                    <span>Buscando por "<strong>${escapeHTML(trimmed)}</strong>"...</span>
                </div>
            `;
            dropdown.classList.add('is-open');

            const controller = new AbortController();
            currentAbort = controller;

            const url = `${ajaxUrl}?action=centralmidi_live_search&q=${encodeURIComponent(trimmed)}`;

            fetch(url, { signal: controller.signal })
                .then(res => res.json())
                .then(res => {
                    if (res && res.success && res.data) {
                        renderResults(res.data, trimmed);
                    } else {
                        closeDropdown();
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        closeDropdown();
                    }
                });
        }

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performSearch(input.value);
            }, 250);
        });

        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 2 && !dropdown.classList.contains('is-open')) {
                performSearch(input.value);
            }
        });

        // Keyboard navigation
        input.addEventListener('keydown', (e) => {
            if (!dropdown.classList.contains('is-open')) return;

            const items = dropdown.querySelectorAll('.cm-suggest-item, .cm-suggest-view-all');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                updateActiveItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                updateActiveItem(items);
            } else if (e.key === 'Enter') {
                if (activeIndex >= 0 && items[activeIndex]) {
                    e.preventDefault();
                    items[activeIndex].click();
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        function updateActiveItem(items) {
            items.forEach((item, idx) => {
                if (idx === activeIndex) {
                    item.classList.add('is-selected');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('is-selected');
                }
            });
        }

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!form.contains(e.target)) {
                closeDropdown();
            }
        });
    });
});
