<?php
/**
 * Search Results Template for WooCommerce Products
 * Customizada com layout de loja e player de áudio
 */

get_header();
?>

<main id="main" class="search-results-page">
    <div class="row category-page-row">
        <div class="col large-12">
            <div class="shop-container">
                
                <?php
                global $wp_query;
                $s = get_search_query();
                ?>
                
                <?php if (!empty($s)) : ?>
                <div class="search-header">
                    <h1 class="search-title">
                        Resultados para: <strong><?php echo esc_html($s); ?></strong>
                    </h1>
                    <p class="search-count"><?php echo $wp_query->found_posts; ?> produto(s) encontrado(s)</p>
                </div>

                <?php if ( have_posts() ) : ?>
                    <div class="products row row-small large-columns-1 medium-columns-1 small-columns-1">
                        <?php while ( have_posts() ) : the_post(); ?>
                            <?php wc_get_template_part( 'content', 'product' ); ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Theme Default Pagination -->
                    <?php 
                    if ($wp_query->max_num_pages > 1) {
                        echo '<nav class="woocommerce-pagination">';
                        $big = 999999999;
                        echo paginate_links(array(
                            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format' => '?paged=%#%',
                            'current' => max(1, get_query_var('paged')),
                            'total' => $wp_query->max_num_pages,
                            'prev_text' => '<i class="icon-angle-left"></i>',
                            'next_text' => '<i class="icon-angle-right"></i>',
                            'type' => 'list',
                        ));
                        echo '</nav>';
                    }
                    ?>
                    
                <?php else : ?>
                    <div class="search-no-results">
                        <p>Nenhum produto encontrado para "<?php echo esc_html($s); ?>".</p>
                        <p>Tente buscar por outro termo ou navegue pelas categorias.</p>
                        <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="button">Ver loja</a>
                    </div>
                <?php endif; ?>
                
                <?php else : ?>
                    <div class="search-no-results">
                        <p>Digite um termo para buscar.</p>
                        <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="button">Ver loja</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>


<?php get_footer(); ?>