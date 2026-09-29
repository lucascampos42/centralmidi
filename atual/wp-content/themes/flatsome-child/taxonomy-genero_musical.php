<?php
/**
 * Template for Genero Musical Taxonomy
 * Layout Moderno Central MIDI usando o componente reutilizável card-midi.php
 */

// Desativa o header antigo de categoria do tema pai Flatsome
remove_action('flatsome_after_header', 'flatsome_category_header');

get_header();

global $wp_query;
$term = get_queried_object();
$term_id = $term->term_id;
$term_name = $term->name;
$term_description = term_description($term_id, 'genero_musical');
$paged = max(1, get_query_var('paged'));
?>

<div class="genero-musical-page cm-archive-page">
    <div class="cm-search-hero cm-archive-hero">
        <div class="shop-container">
            <nav class="cm-search-breadcrumb" aria-label="Navegação">
                <a href="<?php echo esc_url(home_url('/')); ?>"><i class="ri-home-4-line"></i> Início</a>
                <span class="cm-breadcrumb-sep"><i class="ri-arrow-right-s-line"></i></span>
                <a href="<?php echo esc_url(home_url('/midis/')); ?>">Catálogo de MIDIs</a>
                <span class="cm-breadcrumb-sep"><i class="ri-arrow-right-s-line"></i></span>
                <span class="cm-breadcrumb-current"><?php echo esc_html($term_name); ?></span>
            </nav>

            <span class="cm-search-badge"><i class="ri-music-2-line"></i> Gênero Musical</span>
            <h1 class="cm-search-title">MIDIs de <strong><?php echo esc_html($term_name); ?></strong></h1>
            <p class="cm-search-subtitle">
                <?php if ($wp_query->found_posts > 0) : ?>
                    Exibindo <strong><?php echo esc_html($wp_query->found_posts); ?></strong> arquivos MIDI no gênero <?php echo esc_html($term_name); ?>.
                <?php else : ?>
                    Nenhum MIDI encontrado para este gênero.
                <?php endif; ?>
            </p>
            <?php if ($term_description) : ?>
                <div class="cm-term-desc"><?php echo wp_kses_post(wpautop($term_description)); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="shop-container">
        <?php if (have_posts()) : ?>
            <div class="cm-search-toolbar">
                <div class="cm-toolbar-info">
                    <i class="ri-music-2-line"></i>
                    <span>Exibindo <strong><?php echo esc_html($wp_query->post_count); ?></strong> de <strong><?php echo esc_html($wp_query->found_posts); ?></strong> músicas</span>
                </div>
                <div class="cm-toolbar-actions">
                    <a href="<?php echo esc_url(home_url('/midis/')); ?>" class="cm-btn-catalog-link">
                        <i class="ri-list-check"></i> Ver catálogo completo
                    </a>
                </div>
            </div>

            <!-- Grade de MIDIs usando o componente centralizado card-midi -->
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
            <div class="cm-tracks-grid" id="cm-infinite-grid"
                 data-taxonomy="genero_musical"
                 data-term="<?php echo esc_attr($term->slug); ?>"
                 data-current-page="<?php echo esc_attr($paged); ?>"
                 data-max-pages="<?php echo esc_attr($wp_query->max_num_pages); ?>">
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

            <!-- Loader do Scroll Infinito -->
            <div id="cm-infinite-loader" class="cm-infinite-loader" style="display: none;">
                <div class="cm-loader-spinner"></div>
                <span>Carregando mais músicas...</span>
            </div>

            <div id="cm-infinite-sentinel" style="height: 20px;"></div>

            <!-- Paginação Fallback (caso JS desativado ou link direto) -->
            <?php if ($wp_query->max_num_pages > 1) : ?>
                <div class="cm-pagination-wrapper cm-pagination-fallback">
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
                <i class="ri-music-2-line" style="font-size: 3rem; color: #64748b; display: block; margin-bottom: 15px;"></i>
                <p>Nenhum MIDI encontrado para este gênero.</p>
                <a href="<?php echo esc_url(home_url('/midis/')); ?>" class="cm-btn cm-btn-buy" style="background: #00d284 !important; color: #0c0f17 !important;">
                    Ver catálogo geral de MIDIs
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
