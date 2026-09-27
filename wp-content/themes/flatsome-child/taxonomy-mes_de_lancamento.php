<?php
/**
 * Template for Mes de Lancamento Taxonomy
 *
 * Usa o loop nativo do WooCommerce
 */

get_header();

$term = get_queried_object();
$term_id = $term->term_id;
$term_name = $term->name;
$term_description = term_description($term_id, 'mes_de_lancamento');
?>

<main id="main" class="mes-lancamento-page">
     <div class="row">
         <div class="col large-12">
             
             <div class="mes-header">
                 <h1 class="mes-title">Lançamentos <?php echo esc_html($term_name); ?></h1>
                 <?php if ($term_description) : ?>
                     <p class="mes-desc"><?php echo wp_kses_post(wpautop($term_description)); ?></p>
                 <?php endif; ?>
             </div>
             
             <div class="shop-container">
                 <?php if ( have_posts() ) : ?>
                     <?php woocommerce_product_loop_start(); ?>
                         <?php while ( have_posts() ) : the_post(); ?>
                             <?php wc_get_template_part('content', 'product'); ?>
                         <?php endwhile; ?>
                     <?php woocommerce_product_loop_end(); ?>
                     
                     <?php woocommerce_pagination(); ?>
                     
                 <?php else : ?>
                     <div class="no-midis-found">
                         <p>Nenhum MIDI encontrado para este mês.</p>
                     </div>
                 <?php endif; ?>

                 <div class="back-to-list">
                     <a href="<?php echo home_url('/midis/'); ?>" class="btn-ver-mais">
                         Ver todos os artistas
                     </a>
                 </div>

         </div>
     </div>
</main>

<?php get_footer(); ?>