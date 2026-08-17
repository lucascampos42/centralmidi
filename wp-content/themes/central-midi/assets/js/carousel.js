document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.cm-carousel').forEach((carousel) => {
        const track      = carousel.querySelector('.cm-carousel-track');
        const slides     = Array.from(carousel.querySelectorAll('.cm-carousel-slide'));
        const prevBtn    = carousel.querySelector('.cm-carousel-prev');
        const nextBtn    = carousel.querySelector('.cm-carousel-next');
        const dots       = Array.from(carousel.querySelectorAll('.cm-carousel-dot'));
        const intervalMs = parseInt(carousel.dataset.interval || '6000', 10);

        if (slides.length === 0) {
            return;
        }

        let current = slides.findIndex((s) => s.classList.contains('is-active'));
        if (current < 0) {
            current = 0;
        }
        let timer    = null;
        let touchX   = null;
        const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function goTo(index) {
            index = (index + slides.length) % slides.length;
            slides.forEach((s, i) => s.classList.toggle('is-active', i === index));
            dots.forEach((d, i) => d.classList.toggle('is-active', i === index));
            current = index;
        }

        function goPrev() { goTo(current - 1); }
        function goNext() { goTo(current + 1); }

        function startAutoplay() {
            stopAutoplay();
            if (prefersReducedMotion) {
                return;
            }
            if (slides.length > 1) {
                timer = setInterval(goNext, intervalMs);
            }
        }

        function stopAutoplay() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => { goPrev(); startAutoplay(); });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => { goNext(); startAutoplay(); });
        }
        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                goTo(parseInt(dot.dataset.index, 10));
                startAutoplay();
            });
        });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);

        track.addEventListener('touchstart', (e) => {
            touchX = e.changedTouches[0].clientX;
            stopAutoplay();
        }, { passive: true });

        track.addEventListener('touchend', (e) => {
            if (touchX === null) {
                return;
            }
            const delta = e.changedTouches[0].clientX - touchX;
            if (Math.abs(delta) > 40) {
                if (delta < 0) {
                    goNext();
                } else {
                    goPrev();
                }
            }
            touchX = null;
            startAutoplay();
        }, { passive: true });

        track.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') { goPrev(); startAutoplay(); }
            if (e.key === 'ArrowRight') { goNext(); startAutoplay(); }
        });

        startAutoplay();
    });
});