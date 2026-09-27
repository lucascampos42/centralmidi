<?php
/**
 * Archive Product Template - Customizada para Pesquisa e Loja
 *
 * @version 8.6.0
 */

get_header( 'shop' );

global $wp_query;
$is_search = is_search();
$s = $is_search ? get_search_query() : '';

if ( $is_search && $s ) {
    echo '<main id="main" class="search-results-page">';
    echo '<div class="row"><div class="col large-12">';
    echo '<div class="shop-container">';
    
    echo '<div class="search-header">';
    echo '<h1 class="search-title">Resultados para: <strong>' . esc_html( $s ) . '</strong></h1>';
    echo '<p class="search-count">' . $wp_query->found_posts . ' produto(s) encontrado(s)</p>';
    echo '</div>';
    
    if ( have_posts() ) {
        echo '<div class="products row row-small large-columns-1 medium-columns-1 small-columns-1">';
        while ( have_posts() ) : the_post();
            wc_get_template_part( 'content', 'product' );
        endwhile;
        echo '</div>';
        
        the_posts_pagination(array(
            'prev_text' => '&larr;',
            'next_text' => '&rarr;',
            'mid_size' => 2,
        ));
    } else {
        echo '<div class="search-no-results">';
        echo '<p>Nenhum produto encontrado para "' . esc_html( $s ) . '".</p>';
        echo '<a href="' . wc_get_page_permalink('shop') . '" class="button primary">Ver loja</a>';
        echo '</div>';
    }
    
    echo '</div></div></main>';
} else {
    wc_get_template_part( 'layouts/category', flatsome_option( 'category_sidebar' ) );
}

get_footer( 'shop' );