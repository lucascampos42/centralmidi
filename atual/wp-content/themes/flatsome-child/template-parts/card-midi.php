<?php
/**
 * Template Part: MIDI Card (reusable component)
 *
 * Usage:
 *   // 1 produto (o proprio card busca os dados):
 *   get_template_part('template-parts/card-midi', null, array('product_id' => 123));
 *
 *   // N produtos (evita N+1: busque antes e passe o registro pronto):
 *   $dados = CentralMidi_DB::get_midis_by_products($ids);
 *   get_template_part('template-parts/card-midi', null, array(
 *       'product_id' => $id,
 *       'midi'       => $dados[$id],
 *   ));
 *
 * Expects: $args['product_id'] (int), $args['midi'] (array|false - opcional)
 *
 * Todos os campos vêm de CentralMidi_DB (tabelas + taxonomia product_cat).
 * Nenhuma leitura de wp_postmeta neste template.
 */

$cm_product_id = isset($args['product_id']) ? absint($args['product_id']) : 0;
if (!$cm_product_id) {
    return;
}

$cm_midi = (isset($args['midi']) && is_array($args['midi'])) ? $args['midi'] : null;
if (!$cm_midi && class_exists('CentralMidi_DB')) {
    $cm_midi = CentralMidi_DB::get_midi_by_product($cm_product_id);
}
if (!$cm_midi) {
    $cm_midi = array();
}

$cm_product = wc_get_product($cm_product_id);
if (!$cm_product) {
    return;
}

$cm_artista        = isset($cm_midi['artista']) ? $cm_midi['artista'] : '';
$cm_artistas       = isset($cm_midi['artistas']) ? (array)$cm_midi['artistas'] : ($cm_artista ? array($cm_artista) : array());
$cm_artista_ids    = isset($cm_midi['artista_ids']) ? (array)$cm_midi['artista_ids'] : array();
$cm_artista_foto_id = isset($cm_midi['artista_foto_id']) ? (int) $cm_midi['artista_foto_id'] : 0;
$cm_genero         = isset($cm_midi['genero']) ? $cm_midi['genero'] : '';
$cm_mes_lancamento = isset($cm_midi['mes_lancamento']) ? (int) $cm_midi['mes_lancamento'] : 0;
$cm_ano_lancamento = isset($cm_midi['ano_lancamento']) ? (int) $cm_midi['ano_lancamento'] : 0;
$cm_classificacao  = isset($cm_midi['classificacao']) ? $cm_midi['classificacao'] : '';
$cm_demo_audio     = isset($cm_midi['demo_url']) ? $cm_midi['demo_url'] : '';
$cm_title          = get_the_title($cm_product_id);
$cm_clean_title    = function_exists('cmidi_clean_song_title') ? cmidi_clean_song_title($cm_title) : $cm_title;
$cm_class_label    = ($cm_classificacao && class_exists('CentralMidi_DB')) ? CentralMidi_DB::classificacao_label($cm_classificacao) : '';
$cm_product_url    = get_permalink($cm_product_id);
$cm_price_html     = $cm_product ? $cm_product->get_price_html() : '';
?>
<div class="cm-track-card centralmidi-card"
     data-id="<?php echo esc_attr($cm_product_id); ?>"
     data-title="<?php echo esc_attr($cm_clean_title); ?>"
     data-artist="<?php echo esc_attr($cm_artista ? $cm_artista : 'Geral'); ?>"
     data-url="<?php echo esc_url($cm_product_url); ?>"
     data-audio="<?php echo esc_url($cm_demo_audio); ?>">

    <div class="cm-card-cover centralmidi-card-cover">
        <?php
        $cm_badge_text = '';
        $cm_class_upper = strtoupper(trim($cm_classificacao));
        if ($cm_class_upper === 'M') {
            $cm_badge_text = 'Melodia';
        } elseif ($cm_class_upper === 'L') {
            $cm_badge_text = 'Letra';
        } elseif ($cm_class_upper === 'RLM') {
            $cm_badge_text = 'Letra & Melodia';
        }

        $cm_tooltip_text = "Classificação #RLM\n#M = Midis somente com Melodia\n#L = Midis somente com Letra sincronizada\n#RLM = Midis com Melodia e Letra sincronizada\nCaso não haja essa classificação, considere portanto que o midi não tem melodia nem Letra!";
        ?>
        <?php if (!empty($cm_badge_text)) : ?>
            <span class="cm-badge-classificacao cm-badge-<?php echo esc_attr(strtolower($cm_classificacao)); ?>"
                  title="<?php echo esc_attr($cm_tooltip_text); ?>">
                <?php echo esc_html($cm_badge_text); ?>
            </span>
        <?php endif; ?>

        <?php if (has_post_thumbnail($cm_product_id)) : ?>
            <?php echo get_the_post_thumbnail($cm_product_id, 'medium'); ?>
        <?php else :
            $cm_artista_foto = $cm_artista_foto_id ? wp_get_attachment_image($cm_artista_foto_id, 'medium') : '';
            if ($cm_artista_foto) :
                echo $cm_artista_foto;
            else : ?>
                <div class="cm-cover-placeholder">
                    <i class="ri-disc-fill"></i>
                </div>
            <?php endif;
        endif; ?>
    </div>

    <div class="cm-card-content">
        <?php if (!empty($cm_artistas) && is_array($cm_artistas)) : ?>
            <div class="cm-artist-tag">
                <?php
                $rendered_artists = array();
                foreach ($cm_artistas as $index => $art_nome) {
                    $term_id = isset($cm_artista_ids[$index]) ? (int) $cm_artista_ids[$index] : 0;
                    $term_link = $term_id ? get_term_link($term_id, 'product_cat') : '';
                    if ($term_link && !is_wp_error($term_link)) {
                        $rendered_artists[] = '<a href="' . esc_url($term_link) . '" title="' . esc_attr(sprintf(__('Ver músicas de %s', 'centralmidi'), $art_nome)) . '">' . esc_html($art_nome) . '</a>';
                    } else {
                        $rendered_artists[] = esc_html($art_nome);
                    }
                }
                echo implode(' <span class="cm-artist-sep">&amp;</span> ', $rendered_artists);
                ?>
            </div>
        <?php elseif ($cm_artista) : ?>
            <div class="cm-artist-tag"><?php echo esc_html($cm_artista); ?></div>
        <?php endif; ?>

        <h3 class="cm-track-title" title="<?php echo esc_attr($cm_clean_title); ?>">
            <a href="<?php echo esc_url($cm_product_url); ?>"><?php echo esc_html($cm_clean_title); ?></a>
        </h3>

    </div>

    <?php if ($cm_genero || $cm_mes_lancamento) : ?>
        <div class="cm-meta-badges">
            <?php if ($cm_genero) : ?>
                <span class="cm-tag"><i class="ri-music-2-line"></i> <?php echo esc_html($cm_genero); ?></span>
            <?php endif; ?>
            <?php if ($cm_mes_lancamento) : ?>
                <span class="cm-tag"><i class="ri-calendar-line"></i> <?php echo esc_html(CentralMidi_DB::mes_nome($cm_mes_lancamento)); ?><?php echo $cm_ano_lancamento ? ' ' . esc_html($cm_ano_lancamento) : ''; ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="cm-card-footer">
        <div class="cm-price">
            <?php echo $cm_price_html ? wp_kses_post($cm_price_html) : '<span class="cm-price-val">R$ 0,00</span>'; ?>
        </div>

        <div class="cm-card-actions">
            <?php if ($cm_demo_audio) : ?>
                <button type="button" 
                        class="cm-btn cm-btn-demo cm-play-trigger" 
                        title="Ouvir Demonstração" 
                        aria-label="<?php echo esc_attr(sprintf(__('Ouvir demonstração de %s', 'central-midi'), $cm_clean_title)); ?>">
                    <i class="ri-play-fill cm-icon-play"></i>
                    <i class="ri-pause-fill cm-icon-pause"></i>
                    <span class="cm-play-text"><span class="cm-play-text-base">Ouvir</span><span class="cm-play-text-extra"> Demo</span></span>
                </button>
            <?php endif; ?>

            <a href="<?php echo esc_url('?add-to-cart=' . $cm_product_id); ?>" 
               data-product_id="<?php echo esc_attr($cm_product_id); ?>" 
               class="cm-btn cm-btn-buy add_to_cart_button ajax_add_to_cart"
               title="Comprar MIDI">
                <i class="ri-shopping-cart-line"></i> Comprar
            </a>
        </div>
    </div>
</div>

