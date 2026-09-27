<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type = get_theme_mod( 'related_products', 'slider' );
if ( $type == 'hidden' ) return;

if ( $related_products ) :

	if ( function_exists( 'wp_increase_content_media_count' ) ) {
		$content_media_count = wp_increase_content_media_count( 0 );
		if ( $content_media_count < wp_omit_loading_attr_threshold() ) {
			wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $content_media_count );
		}
	}
	?>

	<div class="related related-products-wrapper product-section">

		<?php
		$heading = apply_filters( 'woocommerce_product_related_products_heading', __( 'Related products', 'woocommerce' ) );
		if ( $heading ) :
			?>
			<h3 class="product-section-title container-width product-section-title-related pt-half pb-half uppercase">
				<?php echo esc_html( $heading ); ?>
			</h3>
		<?php endif; ?>

		<div class="related-products-list">
			<?php wc_set_loop_prop( 'columns', 1 ); ?>
			<?php woocommerce_product_loop_start(); ?>
			<?php foreach ( $related_products as $related_product ) :
				$post_object = get_post( $related_product->get_id() );
				setup_postdata( $GLOBALS['post'] = $post_object );
				wc_get_template_part( 'content', 'product' );
			endforeach; ?>
			<?php woocommerce_product_loop_end(); ?>
		</div>

	</div>
	<?php
endif;

wp_reset_postdata();
