<?php
/**
 * Search Results Template for WooCommerce Products
 * Layout Moderno inspirado no novo tema Central MIDI
 */

// Desativa o header antigo de categoria do tema pai Flatsome
remove_action('flatsome_after_header', 'flatsome_category_header');

get_header();

global $wp_query;
$s = get_search_query();
if (empty($s)) {
    $s = $wp_query->get('cm_search_term') ?: (isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '');
}
$paged = max(1, get_query_var('paged'));
?>

<div class="search-results-page">
    <div class="cm-search-hero">
        <div class="shop-container">
            <nav class="cm-search-breadcrumb" aria-label="Navegação">
                <a href="<?php echo esc_url(home_url('/')); ?>"><i class="ri-home-4-line"></i> Início</a>
                <span class="cm-breadcrumb-sep"><i class="ri-arrow-right-s-line"></i></span>
                <a href="<?php echo esc_url(home_url('/midis/')); ?>">Catálogo de MIDIs</a>
                <span class="cm-breadcrumb-sep"><i class="ri-arrow-right-s-line"></i></span>
                <span class="cm-breadcrumb-current">Busca: "<?php echo esc_html($s); ?>"</span>
            </nav>

            <span class="cm-search-badge"><i class="ri-search-line"></i> Busca no Catálogo</span>
            <h1 class="cm-search-title">Resultados para: "<strong><?php echo esc_html($s); ?></strong>"</h1>
            <p class="cm-search-subtitle">
                <?php if ($wp_query->found_posts > 0) : ?>
                    Encontramos <strong><?php echo esc_html($wp_query->found_posts); ?></strong> MIDI(s) para o termo pesquisado.
                <?php else : ?>
                    Nenhum MIDI encontrado para este termo.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="shop-container">
        <?php if (have_posts()) : ?>
            <div class="cm-search-toolbar">
                <div class="cm-toolbar-info">
                    <i class="ri-music-2-line"></i>
                    <span>Exibindo <strong><?php echo esc_html($wp_query->post_count); ?></strong> de <strong><?php echo esc_html($wp_query->found_posts); ?></strong> resultados</span>
                </div>
                <div class="cm-toolbar-actions">
                    <a href="<?php echo esc_url(home_url('/midis/')); ?>" class="cm-btn-catalog-link">
                        <i class="ri-list-check"></i> Ver catálogo completo
                    </a>
                </div>
            </div>
            <?php
            // Uma única leitura em lote para todos os cards da página de resultados.
            $cm_result_ids = array();
            if (have_posts()) {
                foreach ($wp_query->posts as $cm_post) {
                    $cm_result_ids[] = (int) $cm_post->ID;
                }
            }
            $cm_result_map = ($cm_result_ids && class_exists('CentralMidi_DB'))
                ? CentralMidi_DB::get_midis_by_products($cm_result_ids)
                : array();
            if (function_exists('cmidi_prime_cards')) {
                cmidi_prime_cards($cm_result_ids);
            }
            ?>
            <div class="cm-tracks-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php 
                    $pid = get_the_ID();
                    get_template_part('template-parts/card-midi', null, array(
                        'product_id' => $pid,
                        'midi'       => isset($cm_result_map[$pid]) ? $cm_result_map[$pid] : null,
                    )); 
                    ?>
                <?php endwhile; ?>
            </div>

            <!-- Paginação Moderna -->
            <?php if ($wp_query->max_num_pages > 1) : ?>
                <div class="cm-pagination-wrapper">
                    <?php
                    $big = 999999999;
                    echo paginate_links(array(
                        'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format'    => '?paged=%#%',
                        'current'   => $paged,
                        'total'     => $wp_query->max_num_pages,
                        'prev_text' => '<i class="ri-arrow-left-s-line"></i>',
                        'next_text' => '<i class="ri-arrow-right-s-line"></i>',
                    ));
                    ?>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="cm-search-empty">
                <i class="ri-search-eye-line" style="font-size: 3rem; color: #64748b; display: block; margin-bottom: 15px;"></i>
                <p>Nenhum produto encontrado para "<?php echo esc_html($s); ?>".</p>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="cm-btn cm-btn-buy" style="background: #00d284 !important; color: #0c0f17 !important;">
                    Ver catálogo da loja
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Integração instantânea com o player Howler existente
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cm-play-trigger').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var card = this.closest('.cm-track-card');
            if (!card) return;
            var audioUrl = card.getAttribute('data-audio');
            var productId = card.getAttribute('data-id');
            if (audioUrl && typeof playAudio === 'function') {
                playAudio(audioUrl, null, this);
            }
        });
    });
});
</script>

<?php get_footer(); ?>