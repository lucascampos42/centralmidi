document.addEventListener('DOMContentLoaded', () => {
    const audioElement = document.getElementById('cm-audio-element');
    const playerBar = document.getElementById('cm-global-player');
    const playerTitle = document.getElementById('cm-player-title');
    const playerArtist = document.getElementById('cm-player-artist');
    const playerBuyLink = document.getElementById('cm-player-buy-link');
    const btnMainPlay = document.getElementById('cm-btn-main-play');
    const iconMainPlay = document.getElementById('cm-main-play-icon');
    const btnStop = document.getElementById('cm-btn-stop');
    const btnPrev = document.getElementById('cm-btn-prev');
    const btnNext = document.getElementById('cm-btn-next');
    const btnClose = document.getElementById('cm-btn-close-player');
    const currentTimeEl = document.getElementById('cm-current-time');
    const durationTimeEl = document.getElementById('cm-duration-time');
    const progressBar = document.getElementById('cm-progress-bar');
    const progressFill = document.getElementById('cm-progress-fill');
    const volumeSlider = document.getElementById('cm-volume-slider');
    const volumeIcon = document.getElementById('cm-volume-icon');

    let currentCard = null;
    let currentAudioUrl = '';
    let playlistQueue = [];
    let queueIndex = -1;

    // Restore volume from localStorage
    const savedVolume = localStorage.getItem('cm-player-volume');
    if (audioElement && volumeSlider) {
        const initialVol = savedVolume !== null ? parseFloat(savedVolume) : 0.8;
        audioElement.volume = initialVol;
        volumeSlider.value = initialVol;
        updateVolumeIcon(initialVol);
    }

    function formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) return '00:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    function updatePlayIcon(isPlaying) {
        if (!iconMainPlay) return;
        iconMainPlay.className = isPlaying ? 'ri-pause-fill' : 'ri-play-fill';
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
        const triggers = (card.querySelectorAll && typeof card.querySelectorAll === 'function')
            ? card.querySelectorAll('.cm-play-trigger')
            : [card];
        triggers.forEach(trig => {
            trig.classList.toggle('playing', isPlaying);
            const label = isPlaying ? 'Pausar Demonstração' : 'Ouvir Demonstração MP3';
            trig.setAttribute('title', label);
            trig.setAttribute('aria-label', label);
        });
    }

    function updateMediaSession(title, artist) {
        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: title,
                artist: artist,
                album: 'Central MIDI',
                artwork: [
                    { src: window.location.origin + '/wp-content/themes/central-midi/assets/img/logo.webp', sizes: '512x512', type: 'image/webp' }
                ]
            });

            navigator.mediaSession.setActionHandler('play', () => {
                if (audioElement && audioElement.src) audioElement.play();
            });
            navigator.mediaSession.setActionHandler('pause', () => {
                if (audioElement) audioElement.pause();
            });
            navigator.mediaSession.setActionHandler('previoustrack', playPrevTrack);
            navigator.mediaSession.setActionHandler('nexttrack', playNextTrack);
            navigator.mediaSession.setActionHandler('seekto', (details) => {
                if (details.seekTime !== undefined && audioElement) {
                    audioElement.currentTime = details.seekTime;
                }
            });
        }
    }

    function extractTrackData(source) {
        let audioUrl = '';
        let title = 'Música Sem Título';
        let artist = 'Central MIDI';
        let productUrl = '#';
        let card = null;

        if (source instanceof HTMLElement) {
            card = source.closest('.cm-track-card') || source.closest('.cm-suggest-track') || source;
            audioUrl = source.dataset.audio || (card && card.dataset.audio) || '';
            title = source.dataset.title || (card && card.dataset.title) || 'Música Sem Título';
            artist = source.dataset.artist || (card && card.dataset.artist) || 'Central MIDI';
            productUrl = source.dataset.url || (card && card.dataset.url) || '#';
        } else if (typeof source === 'object' && source !== null) {
            audioUrl = source.audio || '';
            title = source.title || 'Música Sem Título';
            artist = source.artist || 'Central MIDI';
            productUrl = source.url || '#';
            card = source.card || source.element || null;
        }

        return { audioUrl, title, artist, productUrl, card };
    }

    function playTrack(source, newPlaylist = null, playIndex = 0) {
        if (!source || !audioElement) return;

        const track = extractTrackData(source);
        if (!track.audioUrl) return;

        if (newPlaylist && Array.isArray(newPlaylist)) {
            playlistQueue = newPlaylist;
            queueIndex = playIndex;
        } else {
            // If playing an individual track, set it as single item in queue or find its index
            const existingIdx = playlistQueue.findIndex(t => t.audioUrl === track.audioUrl || (t.card && t.card === track.card));
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

        // If clicking the same song toggle play/pause
        if (currentAudioUrl === track.audioUrl) {
            if (audioElement.paused) {
                audioElement.play().catch(() => updatePlayIcon(false));
            } else {
                audioElement.pause();
            }
            return;
        }

        // New song selected
        currentCard = track.card;
        currentAudioUrl = track.audioUrl;

        audioElement.src = track.audioUrl;
        if (playerTitle) playerTitle.textContent = track.title;
        if (playerArtist) playerArtist.textContent = track.artist;
        if (playerBuyLink) {
            playerBuyLink.href = track.productUrl;
            playerBuyLink.hidden = false;
        }

        if (playerBar) playerBar.classList.remove('hidden');
        document.body.classList.add('cm-player-active');

        updateMediaSession(track.title, track.artist);

        audioElement.play().catch(err => {
            console.error('Playback error:', err);
            updatePlayIcon(false);
            setCardPlayingState(currentCard, false);
        });
    }

    function playNextTrack() {
        if (playlistQueue.length > 0 && queueIndex + 1 < playlistQueue.length) {
            queueIndex++;
            playTrack(playlistQueue[queueIndex], playlistQueue, queueIndex);
        } else if (playlistQueue.length > 0 && queueIndex + 1 >= playlistQueue.length) {
            // Loop or stop
            audioElement.pause();
            audioElement.currentTime = 0;
            setCardPlayingState(currentCard, false);
            updatePlayIcon(false);
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

    // Expose global play function
    window.CentralMidiPlayer = {
        playTrack: playTrack,
        playNextTrack: playNextTrack,
        playPrevTrack: playPrevTrack,
        playPlaylist: function(playlist, startIndex = 0) {
            if (playlist && playlist.length > 0) {
                playTrack(playlist[startIndex], playlist, startIndex);
            }
        }
    };

    // Delegated click event for any .cm-play-trigger
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.cm-play-trigger');
        if (!btn) return;
        e.stopPropagation();
        e.preventDefault();
        playTrack(btn);
    });

    // "Reproduzir Lista" button click
    document.addEventListener('click', (e) => {
        const listBtn = e.target.closest('.cm-play-monthly-playlist');
        if (!listBtn) return;
        e.preventDefault();

        const section = listBtn.closest('.cm-month-section') || listBtn.closest('section');
        if (!section) return;

        const cards = Array.from(section.querySelectorAll('.cm-track-card'));
        const playlist = cards.map(c => extractTrackData(c)).filter(t => !!t.audioUrl);

        if (playlist.length > 0) {
            playTrack(playlist[0], playlist, 0);
        }
    });

    // Main Play/Pause Button in Player Bar
    if (btnMainPlay) {
        btnMainPlay.addEventListener('click', () => {
            if (!audioElement.src) return;
            if (audioElement.paused) {
                audioElement.play();
            } else {
                audioElement.pause();
            }
        });
    }

    // Next Track Button
    if (btnNext) {
        btnNext.addEventListener('click', () => {
            playNextTrack();
        });
    }

    // Previous Track Button
    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            playPrevTrack();
        });
    }

    // Stop Button
    if (btnStop) {
        btnStop.addEventListener('click', () => {
            audioElement.pause();
            audioElement.currentTime = 0;
            setCardPlayingState(currentCard, false);
            updatePlayIcon(false);
        });
    }

    // Close Player
    if (btnClose) {
        btnClose.addEventListener('click', () => {
            audioElement.pause();
            audioElement.currentTime = 0;
            setCardPlayingState(currentCard, false);
            updatePlayIcon(false);
            if (playerBuyLink) playerBuyLink.hidden = true;
            playerBar.classList.add('hidden');
            document.body.classList.remove('cm-player-active');
        });
    }

    // Audio Events
    if (audioElement) {
        audioElement.addEventListener('play', () => {
            updatePlayIcon(true);
            if (currentCard) setCardPlayingState(currentCard, true);
        });

        audioElement.addEventListener('pause', () => {
            updatePlayIcon(false);
            if (currentCard) setCardPlayingState(currentCard, false);
        });

        audioElement.addEventListener('timeupdate', () => {
            if (!isNaN(audioElement.duration) && progressBar && progressFill) {
                const percent = (audioElement.currentTime / audioElement.duration) * 100;
                progressFill.style.width = `${percent}%`;
                if (currentTimeEl) currentTimeEl.textContent = formatTime(audioElement.currentTime);
                if (durationTimeEl) durationTimeEl.textContent = formatTime(audioElement.duration);
            }
        });

        audioElement.addEventListener('loadedmetadata', () => {
            if (durationTimeEl) durationTimeEl.textContent = formatTime(audioElement.duration);
        });

        audioElement.addEventListener('ended', () => {
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

    // Seek Click
    if (progressBar) {
        progressBar.addEventListener('click', (e) => {
            if (!audioElement || isNaN(audioElement.duration)) return;
            const rect = progressBar.getBoundingClientRect();
            const clickPos = (e.clientX - rect.left) / rect.width;
            audioElement.currentTime = clickPos * audioElement.duration;
        });
    }

    // Volume Slider & Persistence
    if (volumeSlider) {
        volumeSlider.addEventListener('input', (e) => {
            const val = parseFloat(e.target.value);
            if (audioElement) audioElement.volume = val;
            updateVolumeIcon(val);
            localStorage.setItem('cm-player-volume', String(val));
        });
    }

    // Dropdown Filters
    document.querySelectorAll('.cm-dropdown-menu a').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (!href || !href.includes('filter=')) return;
            e.preventDefault();
            const filterType = href.split('filter=')[1];
            const targetElement = document.getElementById('midis');
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }

            setTimeout(() => {
                let el = null;
                if (filterType === 'artista') el = document.getElementById('centralmidi-artista');
                else if (filterType === 'genero') el = document.getElementById('centralmidi-genero');
                else if (filterType === 'mes') el = document.getElementById('centralmidi-mes_lancamento');
                else if (filterType === 'classificacao') el = document.getElementById('centralmidi-classificacao');
                if (el) el.focus();
            }, 400);
        });
    });
});
