<?php
/**
 * Central Midi Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

function centralmidi_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));

    register_nav_menus(array(
        'primary' => __('Menu Principal', 'central-midi'),
        'footer'  => __('Menu Rodapé', 'central-midi'),
    ));
}
add_action('after_setup_theme', 'centralmidi_setup');

/**
 * Force BRL (R$) currency formatting for WooCommerce
 */
add_filter('woocommerce_currency', function($currency) {
    return 'BRL';
});
add_filter('woocommerce_currency_symbol', function($currency_symbol, $currency) {
    return 'R$';
}, 10, 2);

/**
 * Update header cart count via AJAX fragments
 */
function centralmidi_cart_count_fragments($fragments) {
    ob_start();
    $count = (function_exists('WC') && is_object(WC()->cart)) ? WC()->cart->get_cart_contents_count() : 0;
    ?>
    <span class="cm-cart-count"><?php echo esc_html($count); ?></span>
    <?php
    $fragments['span.cm-cart-count'] = ob_get_clean();
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'centralmidi_cart_count_fragments');

/**
 * Remove 'Downloads' and 'Addresses' tabs from WooCommerce My Account and redirect endpoints
 * (MIDI files are 100% digital and delivered directly via Email and WhatsApp)
 */
function centralmidi_remove_unneeded_account_menu_items($items) {
    if (isset($items['downloads'])) {
        unset($items['downloads']);
    }
    if (isset($items['edit-address'])) {
        unset($items['edit-address']);
    }

    $custom_items = array();
    foreach ($items as $endpoint => $label) {
        switch ($endpoint) {
            case 'dashboard':
                $custom_items[$endpoint] = 'Painel';
                break;
            case 'orders':
                $custom_items[$endpoint] = 'Meus Pedidos';
                break;
            case 'edit-account':
                $custom_items[$endpoint] = 'Detalhes da Conta';
                break;
            case 'customer-logout':
                $custom_items[$endpoint] = 'Sair da Conta';
                break;
            default:
                $custom_items[$endpoint] = $label;
                break;
        }
    }
    return $custom_items;
}
add_filter('woocommerce_account_menu_items', 'centralmidi_remove_unneeded_account_menu_items', 99);

function centralmidi_disable_unneeded_account_endpoints() {
    if (function_exists('is_account_page') && function_exists('is_wc_endpoint_url')) {
        if (is_account_page() && (is_wc_endpoint_url('downloads') || is_wc_endpoint_url('edit-address'))) {
            wp_safe_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }
}
add_action('template_redirect', 'centralmidi_disable_unneeded_account_endpoints');

/**
 * Protect /importar-midis/ - only administrators with manage_options can access
 */
function centralmidi_protect_admin_pages() {
    if (is_page('importar-midis') || is_page_template('page-importar-midis.php')) {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_safe_redirect(wp_login_url(home_url('/importar-midis/')));
            exit;
        }
    }
}
add_action('template_redirect', 'centralmidi_protect_admin_pages');

/**
 * Redirect WooCommerce Shop archive (/loja/) to canonical MIDIs catalog (/midis/)
 */
function centralmidi_redirect_shop_archive() {
    if (function_exists('is_shop') && is_shop()) {
        wp_safe_redirect(centralmidi_catalog_url(), 301);
        exit;
    }
}
add_action('template_redirect', 'centralmidi_redirect_shop_archive');

/**
 * Redirect "Return to Shop" and "Browse products" buttons to canonical /midis/
 */
function centralmidi_return_to_shop_redirect() {
    return centralmidi_catalog_url();
}
add_filter('woocommerce_return_to_shop_redirect', 'centralmidi_return_to_shop_redirect');

// Remove duplicate raw empty cart message to let custom empty-cart template render cleanly
remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);

/**
 * Custom Portuguese (pt_BR) translations for WooCommerce strings
 */
function centralmidi_translate_woocommerce_strings($translated_text, $text, $domain) {
    static $translations = array(
        'Confirm email address' => 'Confirmar endereço de e-mail',
        'Confirm your email address to check for past orders and link them to your account.' => 'Confirme seu endereço de e-mail para verificar pedidos anteriores e vinculá-los à sua conta.',
        'Verify email' => 'Verificar e-mail',
        'Send verification email' => 'Enviar e-mail de verificação',
        'Resend verification email' => 'Reenviar e-mail de verificação',
        'Email verification sent' => 'E-mail de verificação enviado',
        'Check your email' => 'Verifique seu e-mail',
        'No order has been made yet.' => 'Nenhum pedido foi realizado ainda.',
        'Browse products' => 'Ver catálogo de MIDIs',
        'Orders' => 'Pedidos',
        'Order' => 'Pedido',
        'Addresses' => 'Endereços',
        'Account details' => 'Detalhes da conta',
        'Log out' => 'Sair',
        'New in store' => 'Outros Produtos',
        'Novidade na loja' => 'Outros Produtos',
        'Your cart is currently empty!' => 'Seu carrinho está vazio no momento!',
        'Explore our catalog' => 'Explorar nosso catálogo',
    );

    if (isset($translations[$text])) {
        return $translations[$text];
    }

    return $translated_text;
}
add_filter('gettext', 'centralmidi_translate_woocommerce_strings', 20, 3);
add_filter('ngettext', 'centralmidi_translate_woocommerce_strings', 20, 3);

/**
 * Central MIDI site options (WhatsApp, e-mail, PIX) via Customizer.
 */
function centralmidi_get_option($key, $default = '') {
    return get_theme_mod('centralmidi_' . $key, $default);
}

function centralmidi_option_whatsapp() {
    return centralmidi_get_option('whatsapp', '5531984511174');
}

function centralmidi_option_email() {
    return centralmidi_get_option('email', 'contato@centralmidi.com.br');
}

function centralmidi_option_pix() {
    return centralmidi_get_option('pix', 'contato@centralmidi.com.br');
}

/**
 * Catalog page URL. Resolves the published page containing the [centralmidi_catalogo]
 * shortcode, falling back to /midis/.
 */
function centralmidi_catalog_url() {
    static $cached = null;

    if (null !== $cached) {
        return $cached;
    }

    // Resolved (and cached in a single option row) by the plugin when available.
    if (class_exists('CentralMidi_DB') && method_exists('CentralMidi_DB', 'catalog_url')) {
        $cached = CentralMidi_DB::catalog_url();
        return $cached;
    }

    $pages = get_pages(array(
        'post_type'   => 'page',
        'post_status' => 'publish',
        'sort_column' => 'menu_order,ID',
    ));

    foreach ($pages as $page) {
        if (has_shortcode($page->post_content, 'centralmidi_catalogo')) {
            $cached = get_permalink($page->ID);
            return $cached;
        }
    }

    $cached = home_url('/midis/');
    return $cached;
}

function centralmidi_customize_register($wp_customize) {
    $wp_customize->add_section('centralmidi_options', array(
        'title'    => __('Central MIDI - Configurações', 'central-midi'),
        'priority' => 30,
    ));

    $settings = array(
        'whatsapp' => array(
            'label'       => __('WhatsApp (somente números, com DDI)', 'central-midi'),
            'description' => __('Ex: 5531984511174', 'central-midi'),
            'sanitize'    => 'sanitize_text_field',
        ),
        'email'    => array(
            'label'       => __('E-mail de Contato', 'central-midi'),
            'sanitize'    => 'sanitize_email',
        ),
        'pix'      => array(
            'label'       => __('Chave PIX', 'central-midi'),
            'sanitize'    => 'sanitize_text_field',
        ),
    );

    foreach ($settings as $key => $config) {
        $wp_customize->add_setting('centralmidi_' . $key, array(
            'default'           => centralmidi_get_option($key, ''),
            'sanitize_callback' => $config['sanitize'],
        ));
        $wp_customize->add_control('centralmidi_' . $key, array(
            'label'       => $config['label'],
            'description' => isset($config['description']) ? $config['description'] : '',
            'section'     => 'centralmidi_options',
            'type'        => 'text',
        ));
    }
}
add_action('customize_register', 'centralmidi_customize_register');

/**
 * Demo audio player button on the single product page (uses the global player).
 */
function centralmidi_single_demo_audio() {
    global $product;
    if (!$product) {
        return;
    }

    $demo_audio = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_product_demo_url($product->get_id()) : get_post_meta($product->get_id(), '_centralmidi_demo_audio', true);
    if (!$demo_audio) {
        return;
    }

    $artist = get_post_meta($product->get_id(), '_centralmidi_artista', true);
    ?>
    <div class="cm-single-demo">
        <button type="button"
                class="cm-btn cm-btn-primary cm-play-trigger"
                data-audio="<?php echo esc_url($demo_audio); ?>"
                data-title="<?php echo esc_attr(get_the_title($product->get_id())); ?>"
                data-artist="<?php echo esc_attr($artist ? $artist : 'Geral'); ?>"
                data-url="<?php echo esc_url(get_permalink($product->get_id())); ?>"
                aria-label="<?php echo esc_attr(sprintf(__('Ouvir demonstração de %s', 'central-midi'), get_the_title($product->get_id()))); ?>">
            <i class="ri-volume-up-line"></i> Ouvir Demonstração (MP3)
        </button>
    </div>
    <?php
}
add_action('woocommerce_single_product_summary', 'centralmidi_single_demo_audio', 30);

/**
 * Whether a MIDI product is published (available for sale).
 * Products without the meta key default to published.
 */
function centralmidi_product_is_publicado($product_id) {
    $publicado = get_post_meta($product_id, '_centralmidi_publicado', true);
    return '' === $publicado || (int) (bool) $publicado;
}

/**
 * Block purchase of MIDIs that are not yet published (product may still be publish in Woo).
 */
function centralmidi_is_purchasable($purchasable, $product) {
    if ($purchasable && !centralmidi_product_is_publicado($product->get_id())) {
        return false;
    }
    return $purchasable;
}
add_filter('woocommerce_is_purchasable', 'centralmidi_is_purchasable', 10, 2);

/**
 * "Em breve" notice on the single product page when the MIDI is not published.
 */
function centralmidi_single_em_breve() {
    global $product;
    if (!$product || centralmidi_product_is_publicado($product->get_id())) {
        return;
    }
    ?>
    <div class="cm-single-em-breve">
        <i class="ri-time-line"></i>
        <?php esc_html_e('Em breve — este MIDI ainda não está disponível para venda.', 'central-midi'); ?>
    </div>
    <?php
}
add_action('woocommerce_single_product_summary', 'centralmidi_single_em_breve', 31);

/**
 * Hide unpublished MIDIs from the WooCommerce shop/archive listings.
 */
function centralmidi_hide_unpublished_from_archives($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if (!$query->is_post_type_archive('product') && !$query->is_tax(array('product_cat', 'product_tag'))) {
        return;
    }
    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) {
        $meta_query = array();
    }
    $meta_query[] = array(
        'relation' => 'OR',
        array(
            'key'     => '_centralmidi_publicado',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => '_centralmidi_publicado',
            'value'   => '0',
            'compare' => '!=',
        ),
    );
    $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'centralmidi_hide_unpublished_from_archives');

/**
 * Use the artist photo as the product image when the product has no featured image.
 * Covers the single product gallery, cart thumbnails and related products.
 */
function centralmidi_product_image_fallback($image_id, $product) {
    if ($image_id || !class_exists('CentralMidi_DB')) {
        return $image_id;
    }

    $artista_id = (int) get_post_meta($product->get_id(), '_centralmidi_artista_id', true);
    if (!$artista_id) {
        return $image_id;
    }

    $artista = CentralMidi_DB::get_artista($artista_id);
    if ($artista && $artista->foto_id) {
        return (int) $artista->foto_id;
    }

    return $image_id;
}
add_filter('woocommerce_product_get_image_id', 'centralmidi_product_image_fallback', 10, 2);

/**
 * Enqueue scripts and styles
 */
function centralmidi_scripts() {
    $theme_version = wp_get_theme()->get('Version');

    // Google Fonts
    wp_enqueue_style('centralmidi-fonts', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap', array(), null);
    
    // Remixicon for clean modern icons
    wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css', array(), '4.3.0');

    // Theme Styles
    wp_enqueue_style('centralmidi-style', get_template_directory_uri() . '/assets/css/main.css', array(), $theme_version);

    // Audio Player JS
    wp_enqueue_script('centralmidi-player', get_template_directory_uri() . '/assets/js/player.js', array(), $theme_version, true);

    wp_localize_script('centralmidi-player', 'centralMidiData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'themeUrl' => get_template_directory_uri()
    ));

    // Hero Carousel JS (only where the carousel is used)
    if (is_front_page()) {
        wp_enqueue_script('centralmidi-carousel', get_template_directory_uri() . '/assets/js/carousel.js', array(), $theme_version, true);
    }

    // Search autocomplete & live suggestions
    wp_enqueue_script('centralmidi-search', get_template_directory_uri() . '/assets/js/search.js', array('centralmidi-player'), $theme_version, true);

    // Mobile navigation toggle
    wp_enqueue_script('centralmidi-nav', get_template_directory_uri() . '/assets/js/nav.js', array(), $theme_version, true);

    // Cart Toast & AJAX Add to Cart
    wp_enqueue_script('centralmidi-cart-toast', get_template_directory_uri() . '/assets/js/cart-toast.js', array(), $theme_version, true);

    // Catalog AJAX Filtering
    if (is_page('midis') || is_page(22)) {
        wp_enqueue_script('centralmidi-catalog-ajax', get_template_directory_uri() . '/assets/js/catalog.js', array('centralmidi-player'), $theme_version, true);
    }

    // Batch Importer JS (only on /importar-midis/)
    if (is_page_template('page-importar-midis.php') || is_page('importar-midis')) {
        wp_enqueue_script('centralmidi-batch-importer', get_template_directory_uri() . '/assets/js/batch-importer.js', array(), $theme_version, true);
    }

    // Theme (light/dark/system) toggle
    wp_enqueue_script('centralmidi-theme-toggle', get_template_directory_uri() . '/assets/js/theme-toggle.js', array(), $theme_version, true);
}
add_action('wp_enqueue_scripts', 'centralmidi_scripts');

/**
 * Resource hints (preconnect) for external stylesheet origins.
 */
function centralmidi_resource_hints($urls, $relation_type) {
    if ('preconnect' === $relation_type) {
        $urls[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        );
        $urls[] = array(
            'href'        => 'https://cdn.jsdelivr.net',
            'crossorigin' => 'anonymous',
        );
    }
    return $urls;
}
add_filter('wp_resource_hints', 'centralmidi_resource_hints', 10, 2);

/**
 * Register Custom Post Type: slide (hero carousel)
 */
function centralmidi_register_slide_cpt() {
    register_post_type('cm_slide', array(
        'labels' => array(
            'name'               => __('Slides', 'central-midi'),
            'singular_name'      => __('Slide', 'central-midi'),
            'add_new'            => __('Adicionar novo', 'central-midi'),
            'add_new_item'       => __('Adicionar novo Slide', 'central-midi'),
            'edit_item'          => __('Editar Slide', 'central-midi'),
            'new_item'           => __('Novo Slide', 'central-midi'),
            'view_item'          => __('Ver Slide', 'central-midi'),
            'search_items'       => __('Buscar Slides', 'central-midi'),
            'not_found'          => __('Nenhum slide encontrado', 'central-midi'),
            'not_found_in_trash' => __('Nenhum slide na lixeira', 'central-midi'),
            'menu_name'          => __('Slides', 'central-midi'),
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'centralmidi',
        'menu_icon'    => 'dashicons-images-alt2',
        'menu_position'=> 26,
        'supports'     => array('title', 'thumbnail'),
        'hierarchical' => false,
        'has_archive'  => false,
        'rewrite'      => false,
        'capability_type' => 'post',
    ));
}
add_action('init', 'centralmidi_register_slide_cpt');

/**
 * Metabox: slide content fields
 */
function centralmidi_add_slide_metaboxes() {
    add_meta_box(
        'centralmidi_slide_content',
        __('Conteúdo do Slide', 'central-midi'),
        'centralmidi_render_slide_metabox',
        'cm_slide',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'centralmidi_add_slide_metaboxes');

function centralmidi_render_slide_metabox($post) {
    wp_nonce_field('centralmidi_save_slide', 'centralmidi_slide_nonce');

    $badge       = get_post_meta($post->ID, '_cm_slide_badge', true);
    $subtitle    = get_post_meta($post->ID, '_cm_slide_subtitle', true);
    $btn_text    = get_post_meta($post->ID, '_cm_slide_btn_text', true);
    $btn_url     = get_post_meta($post->ID, '_cm_slide_btn_url', true);
    $align       = get_post_meta($post->ID, '_cm_slide_align', true);
    $align       = in_array($align, array('left', 'center', 'right'), true) ? $align : 'left';
    $tonalidade  = get_post_meta($post->ID, '_cm_slide_tonalidade', true);
    $tonalidade  = in_array($tonalidade, array('dark', 'light'), true) ? $tonalidade : 'dark';
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="cm_slide_badge"><?php esc_html_e('Badge', 'central-midi'); ?></label>
                </th>
                <td>
                    <input type="text" id="cm_slide_badge" name="_cm_slide_badge" value="<?php echo esc_attr($badge); ?>" class="regular-text" placeholder="Ex: Catálogo Oficial Central MIDI" />
                    <p class="description"><?php esc_html_e('Rótulo curto exibido acima do título.', 'central-midi'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cm_slide_subtitle"><?php esc_html_e('Subtítulo', 'central-midi'); ?></label>
                </th>
                <td>
                    <textarea id="cm_slide_subtitle" name="_cm_slide_subtitle" rows="3" class="large-text" placeholder="Texto de apoio exibido sob o título."><?php echo esc_textarea($subtitle); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cm_slide_btn_text"><?php esc_html_e('Texto do Botão', 'central-midi'); ?></label>
                </th>
                <td>
                    <input type="text" id="cm_slide_btn_text" name="_cm_slide_btn_text" value="<?php echo esc_attr($btn_text); ?>" class="regular-text" placeholder="Ex: Ver Catálogo" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cm_slide_btn_url"><?php esc_html_e('URL do Botão', 'central-midi'); ?></label>
                </th>
                <td>
                    <input type="url" id="cm_slide_btn_url" name="_cm_slide_btn_url" value="<?php echo esc_url($btn_url); ?>" class="regular-text" placeholder="Ex: https://seusite.com/catalogo-midi/" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Posição do Texto', 'central-midi'); ?></label>
                </th>
                <td>
                    <label style="margin-right:16px;"><input type="radio" name="_cm_slide_align" value="left" <?php checked($align, 'left'); ?>> <?php esc_html_e('Esquerda', 'central-midi'); ?></label>
                    <label style="margin-right:16px;"><input type="radio" name="_cm_slide_align" value="center" <?php checked($align, 'center'); ?>> <?php esc_html_e('Centro', 'central-midi'); ?></label>
                    <label><input type="radio" name="_cm_slide_align" value="right" <?php checked($align, 'right'); ?>> <?php esc_html_e('Direita', 'central-midi'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Tonalidade da Foto', 'central-midi'); ?></label>
                </th>
                <td>
                    <label style="margin-right:16px;"><input type="radio" name="_cm_slide_tonalidade" value="dark" <?php checked($tonalidade, 'dark'); ?>> <?php esc_html_e('Foto Escura (texto claro)', 'central-midi'); ?></label>
                    <label><input type="radio" name="_cm_slide_tonalidade" value="light" <?php checked($tonalidade, 'light'); ?>> <?php esc_html_e('Foto Clara (texto escuro)', 'central-midi'); ?></label>
                    <p class="description"><?php esc_html_e('Define a cor do texto do slide com base na tonalidade da imagem de fundo — independente do tema claro/escuro do site.', 'central-midi'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <p class="description"><?php esc_html_e('Use a Imagem Destacada do slide como imagem de fundo do carousel.', 'central-midi'); ?></p>
    <?php
}

function centralmidi_save_slide($post_id) {
    if (!isset($_POST['centralmidi_slide_nonce']) || !wp_verify_nonce($_POST['centralmidi_slide_nonce'], 'centralmidi_save_slide')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array(
        '_cm_slide_badge'    => 'sanitize_text_field',
        '_cm_slide_subtitle' => 'sanitize_textarea_field',
        '_cm_slide_btn_text' => 'sanitize_text_field',
        '_cm_slide_btn_url'  => 'esc_url_raw',
    );

    foreach ($fields as $field => $sanitizer) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, $sanitizer(wp_unslash($_POST[$field])));
        }
    }

    $align = isset($_POST['_cm_slide_align']) ? sanitize_text_field(wp_unslash($_POST['_cm_slide_align'])) : 'left';
    if (!in_array($align, array('left', 'center', 'right'), true)) {
        $align = 'left';
    }
    update_post_meta($post_id, '_cm_slide_align', $align);

    $tonalidade = isset($_POST['_cm_slide_tonalidade']) ? sanitize_text_field(wp_unslash($_POST['_cm_slide_tonalidade'])) : 'dark';
    if (!in_array($tonalidade, array('dark', 'light'), true)) {
        $tonalidade = 'dark';
    }
    update_post_meta($post_id, '_cm_slide_tonalidade', $tonalidade);
}
add_action('save_post_cm_slide', 'centralmidi_save_slide');

/**
 * Returns carousel slides (ordered by menu_order), cached in a transient.
 */
function centralmidi_get_slides() {
    $cached = get_transient('centralmidi_slides');
    if (false !== $cached) {
        return $cached;
    }

    $query = new WP_Query(array(
        'post_type'      => 'cm_slide',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ));

    $slides = $query->posts;
    set_transient('centralmidi_slides', $slides, DAY_IN_SECONDS);

    return $slides;
}

/**
 * Invalidate the cached slides when a slide is saved or deleted.
 */
function centralmidi_clear_slides_cache($post_id) {
    if ('cm_slide' === get_post_type($post_id)) {
        delete_transient('centralmidi_slides');
    }
}
add_action('save_post_cm_slide', 'centralmidi_clear_slides_cache');
add_action('delete_post', 'centralmidi_clear_slides_cache');

/**
 * AJAX Live Search Suggestions
 */
function centralmidi_ajax_live_search() {
    $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    if (mb_strlen($q) < 2) {
        wp_send_json_success(array(
            'tracks'  => array(),
            'artists' => array(),
            'total'   => 0,
            'query'   => $q,
        ));
    }

    global $wpdb;

    // Search matching artists
    $artists = array();
    if (class_exists('CentralMidi_DB')) {
        $art_results = CentralMidi_DB::get_artistas_alfabetico('', $q);
        if (!empty($art_results)) {
            foreach (array_slice($art_results, 0, 3) as $art) {
                $artists[] = array(
                    'id'    => (int) $art->id,
                    'name'  => $art->nome,
                    'count' => (int) $art->total_midis,
                    'url'   => add_query_arg('artista', urlencode($art->nome), home_url('/artistas/')),
                );
            }
        }
    }

    // Search matching products/MIDIs by title, artist, genre
    $midis_table    = class_exists('CentralMidi_DB') ? CentralMidi_DB::table_name() : $wpdb->prefix . 'centralmidi_midis';
    $artistas_table = class_exists('CentralMidi_DB') ? CentralMidi_DB::artistas_table_name() : $wpdb->prefix . 'centralmidi_artistas';
    $generos_table  = class_exists('CentralMidi_DB') ? CentralMidi_DB::generos_table_name() : $wpdb->prefix . 'centralmidi_generos';

    $like = '%' . $wpdb->esc_like($q) . '%';

    $sql = $wpdb->prepare(
        "SELECT DISTINCT p.ID
         FROM {$wpdb->posts} p
         LEFT JOIN {$midis_table} m ON m.product_id = p.ID
         LEFT JOIN {$artistas_table} a ON a.id = m.artista_id
         LEFT JOIN {$generos_table} g ON g.id = m.genero_id
         WHERE p.post_type = 'product'
           AND p.post_status = 'publish'
           AND (m.publicado = 1 OR m.product_id IS NULL)
           AND (
               p.post_title LIKE %s
               OR a.nome LIKE %s
               OR g.nome LIKE %s
           )
         ORDER BY (CASE WHEN p.post_title LIKE %s THEN 1 WHEN a.nome LIKE %s THEN 2 ELSE 3 END), p.post_title ASC
         LIMIT 6",
        $like,
        $like,
        $like,
        $q . '%',
        $q . '%'
    );

    $product_ids = $wpdb->get_col($sql);
    $tracks = array();

    if (!empty($product_ids)) {
        foreach ($product_ids as $pid) {
            $product = wc_get_product($pid);
            if (!$product) {
                continue;
            }

            $artista       = get_post_meta($pid, '_centralmidi_artista', true);
            $genero        = get_post_meta($pid, '_centralmidi_genero', true);
            $classificacao = class_exists('CentralMidi_DB') ? CentralMidi_DB::sanitize_classificacao(get_post_meta($pid, '_centralmidi_classificacao', true)) : 'M';
            $class_label   = class_exists('CentralMidi_DB') ? CentralMidi_DB::classificacao_label($classificacao) : '';
            $demo_audio    = get_post_meta($pid, '_centralmidi_demo_audio', true);
            $price_html    = $product->get_price_html();
            $thumbnail     = has_post_thumbnail($pid) ? get_the_post_thumbnail_url($pid, 'thumbnail') : '';

            $tracks[] = array(
                'id'            => (int) $pid,
                'title'         => get_the_title($pid),
                'url'           => get_permalink($pid),
                'artista'       => $artista ? $artista : 'Geral',
                'genero'        => $genero,
                'classificacao' => $classificacao,
                'class_label'   => $class_label,
                'price_html'    => $price_html ? $price_html : 'R$ 0,00',
                'demo_audio'    => $demo_audio,
                'thumbnail'     => $thumbnail,
            );
        }
    }

    wp_send_json_success(array(
        'tracks'  => $tracks,
        'artists' => $artists,
        'total'   => count($tracks),
        'query'   => $q,
        'allUrl'  => home_url('/?s=' . urlencode($q)),
    ));
}
add_action('wp_ajax_centralmidi_live_search', 'centralmidi_ajax_live_search');
add_action('wp_ajax_nopriv_centralmidi_live_search', 'centralmidi_ajax_live_search');
