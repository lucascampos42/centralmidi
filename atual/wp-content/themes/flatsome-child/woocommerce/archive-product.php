<?php
/**
 * Archive Product Template - Customizada para Pesquisa e Loja
 *
 * @version 8.6.0
 */

if ( is_search() ) {
    include get_stylesheet_directory() . '/search.php';
    return;
}

get_header( 'shop' );

wc_get_template_part( 'layouts/category', flatsome_option( 'category_sidebar' ) );

get_footer( 'shop' );