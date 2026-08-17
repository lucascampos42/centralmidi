<?php
/**
 * Template Part: MIDI Card (reusable component)
 *
 * Usage:
 *   get_template_part('template-parts/card-midi', null, array('product_id' => 123));
 *
 * Expects: $args['product_id'] (int)
 */

$cm_product_id = isset($args['product_id']) ? absint($args['product_id']) : 0;
if (!$cm_product_id) {
    return;
}

$cm_product = wc_get_product($cm_product_id);
if (!$cm_product) {
    return;
}

$cm_artista        = get_post_meta($cm_product_id, '_centralmidi_artista', true);
$cm_genero         = get_post_meta($cm_product_id, '_centralmidi_genero', true);
$cm_categoria      = get_post_meta($cm_product_id, '_centralmidi_categoria', true);
$cm_mes_lancamento = get_post_meta($cm_product_id, '_centralmidi_mes_lancamento', true);
$cm_classificacao  = class_exists('CentralMidi_DB') ? CentralMidi_DB::sanitize_classificacao(get_post_meta($cm_product_id, '_centralmidi_classificacao', true)) : 'M';
$cm_demo_audio     = get_post_meta($cm_product_id, '_centralmidi_demo_audio', true);
$cm_price_html     = $cm_product->get_price_html();
$cm_title          = get_the_title($cm_product_id);
$cm_class_label    = class_exists('CentralMidi_DB') ? CentralMidi_DB::classificacao_label($cm_classificacao) : '';
$cm_product_url    = get_permalink($cm_product_id);
?>
<div class="cm-track-card centralmidi-card"
     data-id="<?php echo esc_attr($cm_product_id); ?>"
     data-title="<?php echo esc_attr($cm_title); ?>"
     data-artist="<?php echo esc_attr($cm_artista ? $cm_artista : 'Geral'); ?>"
     data-url="<?php echo esc_url($cm_product_url); ?>"
     data-audio="<?php echo esc_url($cm_demo_audio); ?>">

    <div class="cm-card-cover centralmidi-card-cover">
        <?php if (has_post_thumbnail($cm_product_id)) : ?>
            <?php echo get_the_post_thumbnail($cm_product_id, 'medium'); ?>
        <?php else : ?>
            <div class="cm-cover-placeholder">
                <i class="ri-disc-fill"></i>
            </div>
        <?php endif; ?>

        <?php if ($cm_demo_audio) : ?>
            <button type="button" class="cm-play-trigger" title="Ouvir Demonstração MP3" aria-label="<?php echo esc_attr(sprintf(__('Ouvir demonstração de %s', 'central-midi'), $cm_title)); ?>">
                <i class="ri-play-fill cm-icon-play"></i>
                <i class="ri-pause-fill cm-icon-pause"></i>
            </button>
        <?php endif; ?>

        <span class="centralmidi-badge-class class-<?php echo esc_attr(strtolower($cm_classificacao)); ?>"
              data-tooltip="<?php echo esc_attr('#' . $cm_classificacao . ': ' . $cm_class_label); ?>"
              title="<?php echo esc_attr('#' . $cm_classificacao . ': ' . $cm_class_label); ?>">
            #<?php echo esc_html($cm_classificacao); ?>
        </span>
    </div>

    <div class="cm-card-info">
        <?php if ($cm_artista) : ?>
            <span class="cm-artist-tag"><?php echo esc_html($cm_artista); ?></span>
        <?php endif; ?>

        <h3 class="cm-track-title"><?php echo esc_html($cm_title); ?></h3>

        <div class="cm-meta-badges">
            <?php if ($cm_genero) : ?>
                <span class="cm-tag"><i class="ri-music-2-line"></i> <?php echo esc_html($cm_genero); ?></span>
            <?php endif; ?>
            <?php if ($cm_categoria) : ?>
                <span class="cm-tag"><i class="ri-price-tag-3-line"></i> <?php echo esc_html($cm_categoria); ?></span>
            <?php endif; ?>
            <?php if ($cm_mes_lancamento) : ?>
                <span class="cm-tag"><i class="ri-calendar-line"></i> <?php echo esc_html(CentralMidi_DB::mes_nome($cm_mes_lancamento)); ?></span>
            <?php endif; ?>
        </div>

        <div class="cm-card-footer">
            <div class="cm-price">
                <?php echo $cm_price_html ? wp_kses_post($cm_price_html) : '<span class="cm-price-val">R$ 0,00</span>'; ?>
            </div>
            <div class="cm-actions">
                <?php if ($cm_demo_audio) : ?>
                    <button type="button" class="cm-btn cm-btn-primary cm-btn-play-inline cm-play-trigger" aria-label="<?php echo esc_attr(sprintf(__('Ouvir demonstração de %s', 'central-midi'), $cm_title)); ?>">
                        <i class="ri-volume-up-line"></i> Ouvir Demonstração
                    </button>
                <?php else : ?>
                    <span class="cm-btn cm-btn-outline" style="opacity: 0.6; cursor: default;">
                        Sem áudio demo
                    </span>
                <?php endif; ?>
                <a href="<?php echo esc_url($cm_product_url); ?>" class="cm-btn cm-btn-outline cm-btn-buy">
                    <i class="ri-shopping-cart-line"></i> Comprar
                </a>
            </div>
        </div>
    </div>
</div>