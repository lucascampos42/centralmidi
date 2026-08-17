<?php
/**
 * WooCommerce Wrapper - Central Midi
 *
 * Applies the theme layout around all WooCommerce content
 * (single product, cart, checkout, my-account, etc.).
 */
get_header();

echo '<div class="cm-container cm-wc-wrap">';
woocommerce_content();
echo '</div>';

get_footer();