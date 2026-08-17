<?php
/**
 * Empty cart page - Central MIDI
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined('ABSPATH') || exit;

?>
<div class="cm-empty-cart-card">
    <div class="cm-empty-cart-icon-wrap">
        <i class="ri-shopping-bag-3-line cm-empty-cart-icon"></i>
    </div>
    <h2 class="cm-empty-cart-heading"><?php esc_html_e('Seu carrinho está vazio no momento!', 'central-midi'); ?></h2>
    <p class="cm-empty-cart-subtext">
        <?php esc_html_e('Você ainda não adicionou nenhuma música ao seu carrinho. Explore nosso catálogo de lançamentos e sucessos disponíveis para download imediato.', 'central-midi'); ?>
    </p>
    <div class="cm-empty-cart-actions">
        <a href="<?php echo esc_url(centralmidi_catalog_url()); ?>" class="cm-btn cm-btn-primary cm-empty-cart-btn">
            <i class="ri-search-line"></i> <?php esc_html_e('Explorar Catálogo Completo', 'central-midi'); ?>
        </a>
    </div>
</div>

<?php
// Query suggested products to show in the exact canonical format of home & catalog
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
);
$suggested_products = get_posts($args);

if (!empty($suggested_products)) :
?>
<section class="cm-cart-suggestions-section">
    <div class="cm-section-header">
        <div>
            <span class="cm-badge"><i class="ri-sparkling-fill"></i> Sugestões</span>
            <h2 class="cm-section-title"><?php esc_html_e('Outros Produtos em Destaque', 'central-midi'); ?></h2>
            <p class="cm-section-subtitle"><?php esc_html_e('Confira algumas das faixas mais procuradas do catálogo:', 'central-midi'); ?></p>
        </div>
        <div>
            <a href="<?php echo esc_url(centralmidi_catalog_url()); ?>" class="cm-btn cm-btn-outline">
                <?php esc_html_e('Ver Todo o Catálogo', 'central-midi'); ?> <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    </div>

    <div class="cm-tracks-grid">
        <?php foreach ($suggested_products as $prod) : ?>
            <?php get_template_part('template-parts/card-midi', null, array('product_id' => $prod->ID)); ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
