<?php
/**
 * Footer Template with Global Fixed Audio Player
 */
?>
</main>

<footer class="cm-footer">
    <div class="cm-container cm-footer-content">
        <div class="cm-footer-brand">
            <div class="cm-logo">
                <img class="cm-logo-img" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo.webp'); ?>" alt="Central MIDI" width="160" height="42" />
            </div>
            <p>Catálogo oficial de arquivos MIDI com demonstração em áudio e classificação especializada.</p>
        </div>
        <div class="cm-footer-copy">
            <p>&copy; <?php echo date('Y'); ?> Central Midi. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>

<!-- Global Fixed Audio Player Bar -->
<div id="cm-global-player" class="cm-player-bar hidden">
    <div class="cm-container cm-player-inner">
        <div class="cm-player-track-info">
            <div class="cm-player-icon"><i class="ri-disc-line ri-spin"></i></div>
            <div class="cm-player-details">
                <div class="cm-player-title" id="cm-player-title">Nenhuma faixa selecionada</div>
                <div class="cm-player-artist" id="cm-player-artist">-</div>
            </div>
        </div>

        <div class="cm-player-controls-center">
            <div class="cm-player-buttons">
                <button type="button" class="cm-player-btn" id="cm-btn-prev" title="Reiniciar" aria-label="<?php esc_attr_e('Reiniciar faixa', 'central-midi'); ?>"><i class="ri-skip-back-fill"></i></button>
                <button type="button" class="cm-player-btn cm-btn-play-pause" id="cm-btn-main-play" title="Tocar / Pausar" aria-label="<?php esc_attr_e('Tocar / Pausar', 'central-midi'); ?>"><i class="ri-play-fill" id="cm-main-play-icon"></i></button>
                <button type="button" class="cm-player-btn" id="cm-btn-stop" title="Parar" aria-label="<?php esc_attr_e('Parar', 'central-midi'); ?>"><i class="ri-stop-fill"></i></button>
            </div>
            <div class="cm-player-timeline">
                <span class="cm-time" id="cm-current-time">00:00</span>
                <div class="cm-progress-container" id="cm-progress-bar">
                    <div class="cm-progress-fill" id="cm-progress-fill"></div>
                </div>
                <span class="cm-time" id="cm-duration-time">00:00</span>
            </div>
        </div>

        <div class="cm-player-actions">
            <a id="cm-player-buy-link" href="#" class="cm-btn cm-btn-primary cm-player-buy" hidden>
                <i class="ri-shopping-cart-line"></i> Comprar
            </a>
            <div class="cm-volume-wrapper">
                <i class="ri-volume-up-line" id="cm-volume-icon"></i>
                <label class="cm-visually-hidden" for="cm-volume-slider"><?php esc_html_e('Volume', 'central-midi'); ?></label>
                <input type="range" min="0" max="1" step="0.05" value="0.8" id="cm-volume-slider" class="cm-volume-slider">
            </div>
            <button type="button" id="cm-btn-close-player" class="cm-btn-icon" title="Fechar Player" aria-label="<?php esc_attr_e('Fechar player', 'central-midi'); ?>"><i class="ri-close-line"></i></button>
        </div>
    </div>
</div>

<audio id="cm-audio-element" preload="none"></audio>

<?php wp_footer(); ?>
</body>
</html>
