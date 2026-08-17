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
    const btnClose = document.getElementById('cm-btn-close-player');
    const currentTimeEl = document.getElementById('cm-current-time');
    const durationTimeEl = document.getElementById('cm-duration-time');
    const progressBar = document.getElementById('cm-progress-bar');
    const progressFill = document.getElementById('cm-progress-fill');
    const volumeSlider = document.getElementById('cm-volume-slider');
    const volumeIcon = document.getElementById('cm-volume-icon');

    let currentCard = null;
    let currentAudioUrl = '';

    function formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) return '00:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    function updatePlayIcon(isPlaying) {
        if (isPlaying) {
            iconMainPlay.className = 'ri-pause-fill';
        } else {
            iconMainPlay.className = 'ri-play-fill';
        }
    }

    function playTrack(source) {
        if (!source) return;

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
        } else if (typeof source === 'object') {
            audioUrl = source.audio || '';
            title = source.title || 'Música Sem Título';
            artist = source.artist || 'Central MIDI';
            productUrl = source.url || '#';
            card = source.element || null;
        }

        if (!audioUrl) return;

        if (currentCard && currentCard !== card) {
            currentCard.classList.remove('playing');
        }

        // If clicking the same song
        if (currentAudioUrl === audioUrl) {
            if (audioElement.paused) {
                audioElement.play().then(() => updatePlayIcon(true)).catch(() => updatePlayIcon(false));
                if (card) card.classList.add('playing');
            } else {
                audioElement.pause();
                if (card) card.classList.remove('playing');
                updatePlayIcon(false);
            }
            return;
        }

        // New song selected
        currentCard = card;
        currentAudioUrl = audioUrl;

        audioElement.src = audioUrl;
        if (playerTitle) playerTitle.textContent = title;
        if (playerArtist) playerArtist.textContent = artist;
        if (playerBuyLink) {
            playerBuyLink.href = productUrl;
            playerBuyLink.hidden = false;
        }

        if (playerBar) playerBar.classList.remove('hidden');
        document.body.classList.add('cm-player-active');
        if (card) card.classList.add('playing');

        audioElement.play().then(() => {
            updatePlayIcon(true);
        }).catch(err => {
            console.error('Playback error:', err);
            updatePlayIcon(false);
        });
    }

    // Expose global play function
    window.CentralMidiPlayer = {
        playTrack: playTrack,
        playFromElement: function(el) {
            playTrack(el);
        }
    };

    // Delegated click event for any .cm-play-trigger (static or dynamic)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.cm-play-trigger');
        if (!btn) return;
        e.stopPropagation();
        e.preventDefault();
        playTrack(btn);
    });

    // Main Play/Pause Button in Player Bar
    if (btnMainPlay) {
        btnMainPlay.addEventListener('click', () => {
            if (!audioElement.src) return;
            if (audioElement.paused) {
                audioElement.play();
                if (currentCard) currentCard.classList.add('playing');
                updatePlayIcon(true);
            } else {
                audioElement.pause();
                if (currentCard) currentCard.classList.remove('playing');
                updatePlayIcon(false);
            }
        });
    }

    // Stop Button
    if (btnStop) {
        btnStop.addEventListener('click', () => {
            audioElement.pause();
            audioElement.currentTime = 0;
            if (currentCard) currentCard.classList.remove('playing');
            updatePlayIcon(false);
        });
    }

    // Restart Button
    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            audioElement.currentTime = 0;
            if (audioElement.paused) {
                audioElement.play();
                if (currentCard) currentCard.classList.add('playing');
                updatePlayIcon(true);
            }
        });
    }

    // Close Player
    if (btnClose) {
        btnClose.addEventListener('click', () => {
            audioElement.pause();
            audioElement.currentTime = 0;
            if (currentCard) currentCard.classList.remove('playing');
            updatePlayIcon(false);
            if (playerBuyLink) playerBuyLink.hidden = true;
            playerBar.classList.add('hidden');
            document.body.classList.remove('cm-player-active');
        });
    }

    // Audio Events
    audioElement.addEventListener('timeupdate', () => {
        if (!isNaN(audioElement.duration)) {
            const percent = (audioElement.currentTime / audioElement.duration) * 100;
            progressFill.style.width = `${percent}%`;
            currentTimeEl.textContent = formatTime(audioElement.currentTime);
            durationTimeEl.textContent = formatTime(audioElement.duration);
        }
    });

    audioElement.addEventListener('loadedmetadata', () => {
        durationTimeEl.textContent = formatTime(audioElement.duration);
    });

    audioElement.addEventListener('ended', () => {
        if (currentCard) currentCard.classList.remove('playing');
        updatePlayIcon(false);
        progressFill.style.width = '0%';
        currentTimeEl.textContent = '00:00';
    });

    // Seek Click
    if (progressBar) {
        progressBar.addEventListener('click', (e) => {
            if (isNaN(audioElement.duration)) return;
            const rect = progressBar.getBoundingClientRect();
            const clickPos = (e.clientX - rect.left) / rect.width;
            audioElement.currentTime = clickPos * audioElement.duration;
        });
    }

    // Volume Slider
    if (volumeSlider) {
        volumeSlider.addEventListener('input', (e) => {
            const val = parseFloat(e.target.value);
            audioElement.volume = val;
            if (val === 0) {
                volumeIcon.className = 'ri-volume-mute-line';
            } else if (val < 0.5) {
                volumeIcon.className = 'ri-volume-down-line';
            } else {
                volumeIcon.className = 'ri-volume-up-line';
            }
        });
    }

    // Handle Dropdown Filter Clicks
    document.querySelectorAll('.cm-dropdown-menu a').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (!href || !href.includes('filter=')) {
                return;
            }
            e.preventDefault();
            const filterType = href.split('filter=')[1];
            const targetElement = document.getElementById('midis');
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }

            // Focus the corresponding select input
            setTimeout(() => {
                let el = null;
                if (filterType === 'artista') {
                    el = document.getElementById('centralmidi-artista');
                } else if (filterType === 'genero') {
                    el = document.getElementById('centralmidi-genero');
                } else if (filterType === 'mes') {
                    el = document.getElementById('centralmidi-mes_lancamento');
                } else if (filterType === 'classificacao') {
                    el = document.getElementById('centralmidi-classificacao');
                }
                if (el) el.focus();
            }, 400);
        });
    });
});
