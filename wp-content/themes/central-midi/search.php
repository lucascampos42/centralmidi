<?php
/**
 * Search Results Template - Central Midi
 * Searches WooCommerce products and renders them with the MIDI card component.
 */
get_header();

$search_term = get_search_query();
$paged       = max(1, get_query_var('paged'));

// Match by title/content (native WP search) + artist/genre (custom table).
$wp_ids = array();
if ($search_term) {
    $title_query = new WP_Query(array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $search_term,
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ));
    $wp_ids = $title_query->posts;
}

$db_ids = (class_exists('CentralMidi_DB') && $search_term) ? CentralMidi_DB::search_by_term($search_term) : array();

$merged_ids = array_unique(array_merge($wp_ids, $db_ids));
if (empty($merged_ids)) {
    $merged_ids = array(-1);
}

$search_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'post__in'       => $merged_ids,
    'posts_per_page' => 12,
    'paged'          => $paged,
);

$search_query = new WP_Query($search_args);
?>

<div class="cm-hero-banner cm-hero-banner--compact">
    <div class="cm-container">
        <span class="cm-badge"><i class="ri-search-line"></i> Busca no Catálogo</span>
        <h1 class="cm-page-title">Resultados para: "<?php echo esc_html($search_term); ?>"</h1>
        <p class="cm-hero-subtitle">Encontramos <strong><?php echo esc_html($search_query->found_posts); ?></strong> MIDI(s) para o termo pesquisado.</p>
    </div>
</div>

<div class="cm-container cm-search-results">
    <?php if ($search_query->have_posts()) : ?>
        <div class="cm-tracks-grid">
            <?php while ($search_query->have_posts()) : $search_query->the_post(); ?>
                <?php get_template_part('template-parts/card-midi', null, array('product_id' => get_the_ID())); ?>
            <?php endwhile; ?>
        </div>

        <?php if ($search_query->max_num_pages > 1) : ?>
            <div class="cm-pagination">
                <?php
                echo paginate_links(array(
                    'total'   => $search_query->max_num_pages,
                    'current' => $paged,
                ));
                ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="centralmidi-empty cm-search-empty">
            <i class="ri-search-eye-line" style="font-size: 2.5rem; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
            <p>Nenhum MIDI encontrado para o termo pesquisado.</p>
            <a href="<?php echo esc_url(home_url('/midis/')); ?>" class="cm-btn cm-btn-primary" style="margin-top: 16px;">
                Ver catálogo completo
            </a>
        </div>
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>
<?php get_footer(); ?>