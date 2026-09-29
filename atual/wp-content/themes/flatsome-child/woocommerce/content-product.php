<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

// Check stock status.
$out_of_stock = ! $product->is_in_stock();

// Extra post classes.
$classes   = array();
$classes[] = 'product-small';
$classes[] = 'col';
$classes[] = 'has-hover';

if ( $out_of_stock ) $classes[] = 'out-of-stock';

?>
<div <?php wc_product_class( $classes, $product ); ?>>
	<div class="col-inner">
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
	<div class="product-small box <?php echo flatsome_product_box_class(); ?>">
		<div class="box-image">
			<div class="<?php echo flatsome_product_box_image_class(); ?>">
				<a href="<?php echo get_the_permalink(); ?>">
					<?php
						/**
						 *
						 * @hooked woocommerce_get_alt_product_thumbnail - 11
						 * @hooked woocommerce_template_loop_product_thumbnail - 10
						 */
						do_action( 'flatsome_woocommerce_shop_loop_images' );
					?>
				</a>
			</div>
			<div class="image-tools is-small top right show-on-hover">
				<?php do_action( 'flatsome_product_box_tools_top' ); ?>
			</div>
			<div class="image-tools is-small hide-for-small bottom left show-on-hover">
				<?php do_action( 'flatsome_product_box_tools_bottom' ); ?>
			</div>
			<div class="image-tools <?php echo flatsome_product_box_actions_class(); ?>">
				<?php do_action( 'flatsome_product_box_actions' ); ?>
			</div>
			<?php if ( $out_of_stock ) { ?><div class="out-of-stock-label"><?php _e( 'Out of stock', 'woocommerce' ); ?></div><?php } ?>
		</div>

		<div class="box-text <?php echo flatsome_product_box_text_class(); ?>">
			<?php
				do_action( 'woocommerce_before_shop_loop_item_title' );

				echo '<div class="title-wrapper">';
				do_action( 'woocommerce_shop_loop_item_title' );
				// Gênero, RLM, Mês inside title-wrapper
				$product_id = $product->get_id();
				$midi       = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_midi_by_product($product_id) : null;
				$generos = get_the_terms($product_id, 'genero_musical');
				$rlm_val = $midi ? $midi['classificacao'] : '';
				$meses   = get_the_terms($product_id, 'mes_de_lancamento');

				if ($generos || $rlm_val || $meses) {
					echo '<div class="product-metadata-wrapper">';
					if ($generos && !is_wp_error($generos)) {
						foreach ($generos as $g) {
							echo '<span class="genero-tag"><a href="' . get_term_link($g) . '">' . esc_html($g->name) . '</a></span>';
						}
					}
					if ($rlm_val) {
						echo '<span class="rlm-tag">#' . esc_html($rlm_val) . '</span>';
					}
					if ($meses && !is_wp_error($meses)) {
						foreach ($meses as $m) {
							echo '<span class="data-tag">' . esc_html($m->name) . '</span>';
						}
					}
					echo '</div>';
				}
				echo '</div>';

				echo '<div class="opt">';
				echo '<div class="price-player-row" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">';
				
				echo '<div class="price-wrapper">';
				do_action( 'woocommerce_after_shop_loop_item_title' );
				echo '</div>';

                // Código Customizado MIDI: Play Audio
                $audio = $midi ? $midi['demo_url'] : '';
                if (!$audio && function_exists('CentralMidi_DB')) {
                    $audio = CentralMidi_DB::get_product_demo_url($product->get_id());
                }
                if($audio) {
                    $player_id = 'player-demo-' . $product->get_id();
                    echo '
                    <div class="demonstracao">
                        <span class="button-audio button-play" onclick="playAudio(\'' . esc_url($audio) . '\', \'' . $player_id . '\', this);">
                            <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <polygon points="6,4 14,10 6,16"/>
                            </svg>
                        </span>
                        <div id="' . $player_id . '" class="player-container">
                            <div class="player-timeline">
                                <div class="player-progress">
                                    <div class="player-progress-fill"></div>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
				echo '</div>';

				do_action( 'flatsome_product_box_after' );

				echo '</div>';
                ?>

		</div>
	</div>
	<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
	</div>
</div>
