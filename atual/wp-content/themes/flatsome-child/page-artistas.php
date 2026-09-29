<?php
/**
 * Template Name: Artistas MIDI
 * Description: Página de listagem de artistas com navegação A-Z
 */

get_header();
?>

<main id="main" class="artistas-page">
    <div class="row category-page-row">
        <div class="col large-12">
            <div class="shop-container">
                
                <?php echo do_shortcode('[cmidi_artistas]'); ?>
                
            </div>
        </div>
    </div>
</main>

<style>
.artistas-page {
    padding: 20px 0;
    min-height: 400px;
}
</style>

<?php get_footer(); ?>
