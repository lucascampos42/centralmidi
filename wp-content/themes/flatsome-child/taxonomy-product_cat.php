<?php
/**
 * Custom Taxonomy Template for Product Categories (Artistas)
 *
 * Usa o loop nativo do WooCommerce + header customizado do artista
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$term = get_queried_object();
$term_id = $term->term_id;
$term_name = $term->name;

// Get category image
$thumbnail_id = get_term_meta( $term_id, 'thumbnail_id', true );
$image_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : wc_placeholder_img_src();

$artist_description = term_description( $term_id, 'product_cat' );
$product_count = $term->count;
?>

<main id="main" class="artista-detalhe-page">
    <div id="content" class="content-area page-wrapper" role="main">
        <div class="row row-main">
            <div class="large-12 medium-12 small-12 col">
                <div class="col-inner">
            
            <div class="artista-header-premium">
                <div class="artista-header-inner">
                    <div class="artista-profile-img">
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $term_name ); ?>">
                    </div>
                    <div class="artista-header-info">
                        <h1 class="artista-header-title"><?php echo esc_html( $term_name ); ?></h1>
                        <div class="artista-header-meta">
                            <span class="meta-item count"><?php echo sprintf( _n( '%d MIDI', '%d MIDs', $product_count, 'flatsome' ), $product_count ); ?></span>
                            <?php if ($artist_description) : ?>
                                <span class="meta-item sep">|</span>
                                <div class="artista-header-desc"><?php echo wp_kses_post(wpautop($artist_description)); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rlm-notice">
                <strong>Classificação #RLM</strong><br>
                #M = Midis somente com Melodia<br>
                #L = Midis somente com Letra sincronizada<br>
                #RLM = Midis com Melodia e Letra sincronizada<br>
                Caso não haja essa classificação, considere portanto que o midi não tem melodia nem Letra!
            </div>

            <div class="shop-container">
                <?php if ( have_posts() ) : ?>
                    <div class="products row row-small row-full-width large-columns-1 medium-columns-1 small-columns-1">
                        <?php while ( have_posts() ) : the_post(); ?>
                            <?php wc_get_template_part('content', 'product'); ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php woocommerce_pagination(); ?>
                    
                <?php else : ?>
                    <div class="no-midis-found">
                        <p>Nenhum MIDI encontrado para este artista.</p>
                    </div>
                <?php endif; ?>

                <div class="back-to-list">
                    <a href="<?php echo home_url('/midis/'); ?>" class="btn-ver-mais">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Voltar para a lista de artistas
                    </a>
                </div>

            </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>