<?php
/**
 * Central Midi Child Theme Functions - Versão Completa e Estável
 */

// Remover Downloads do menu My Account
add_filter( 'woocommerce_account_menu_items', 'cmidi_remove_downloads_menu', 99 );
function cmidi_remove_downloads_menu( $menu_items ) {
    unset( $menu_items['downloads'] );
    return $menu_items;
}

// 0. SEO Meta tags e Canonical
add_action('wp_head', 'cmidi_seo_meta_tags');
function cmidi_seo_meta_tags() {
    if ( is_singular('product') ) {
        global $product;
        if ( $product ) {
            $desc = $product->get_short_description() ?: $product->get_description();
            $desc = wp_strip_all_tags($desc);
            $desc = substr($desc, 0, 160);
            echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
        }
    }
    
    // Canonical URL para todas as páginas
    echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";
}

// 0. Detecção Antecipada de Tema (Prevenção de FOUC / Flash de cor e sincronização com Sistema Operacional)
add_action('wp_head', 'cmidi_early_theme_detection', 1);
function cmidi_early_theme_detection() {
    ?>
    <script>
    (function() {
        try {
            var STORAGE_KEY = 'cmidi_user_theme';
            var savedTheme = localStorage.getItem(STORAGE_KEY);
            var theme = savedTheme;
            if (!theme) {
                // Se ainda não existir valor salvo, detecta a preferência do sistema operacional / PC
                var prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
                theme = prefersLight ? 'light' : 'dark';
            }
            if (theme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        } catch (e) {}
    })();
    </script>
    <?php
}

// Filtro para usar imagem placeholder local
add_filter('woocommerce_placeholder_img_src', 'cmidi_local_placeholder');
function cmidi_local_placeholder($src) {
    $local_path = get_stylesheet_directory() . '/assets/images/placeholder.png';
    if (file_exists($local_path)) {
        return get_stylesheet_directory_uri() . '/assets/images/placeholder.png';
    }
    return $src;
}

// 1. Lógica de artistas migrada para o plugin Central MIDI (class-centralmidi-artistas.php)
// Desacoplada do tema com navegação AJAX e SSR unificado.

// 1.1 Formulário de contato nativo e mensagens no painel
require get_stylesheet_directory() . '/inc/cmidi-contato.php';

// Correção e enriquecimento dinâmico do menu (Ícones modernos e links diretos)
add_filter('wp_get_nav_menu_items', 'cmidi_fix_menu_links', 10, 3);
function cmidi_fix_menu_links($items, $menu, $args) {
    foreach ($items as &$item) {
        if ($item->title == 'Midis por Artista' || strpos($item->url, 'artistas') !== false) {
            $item->url = home_url('/artistas/');
            $item->title = '<i class="ri-user-star-line" style="color:#00d284;font-size:16px;"></i> Por Artista';
        } elseif (strpos($item->url, 'genero') !== false || $item->title == 'Midis por Gênero') {
            $item->title = '<i class="ri-music-2-line" style="color:#38bdf8;font-size:16px;"></i> Por Gênero';
        } elseif (strpos($item->url, 'mes-de-lancamento') !== false || $item->title == 'Midis por Mês de Lançamento') {
            $item->title = '<i class="ri-calendar-event-line" style="color:#f59e0b;font-size:16px;"></i> Por Mês de Lançamento';
        }
    }
    return $items;
}

// Botão Alternador de Tema (Claro / Escuro) no menu superior
add_action('flatsome_header_elements', 'cmidi_add_theme_toggle_header_element');
function cmidi_add_theme_toggle_header_element($value) {
    if ($value === 'cart') {
        ?>
        <li class="header-theme-toggle-item">
            <button type="button" id="cmidi-theme-toggle-btn" class="cm-theme-toggle-btn" aria-label="Alternar modo claro e escuro" title="Alternar tema">
                <i class="ri-moon-line cm-theme-icon-dark"></i>
                <i class="ri-sun-line cm-theme-icon-light"></i>
            </button>
        </li>
        <?php
    }
}

// Desativa o menu mobile off-canvas antigo do Flatsome para usarmos nosso menu mobile Fullscreen próprio
add_action('after_setup_theme', function() {
    remove_action('wp_footer', 'flatsome_mobile_menu', 7);
}, 20);

// 2. Carrega CSS e scripts
function enqueue_child_theme_style_and_scripts() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    // ---- Ícones e tipografia do front-end ----
    wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css', array(), '4.3.0' );
    wp_enqueue_style( 'jetbrains-mono', 'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700;800&display=swap', array(), null );

    // Carrega o style.css principal do Child Theme (com versionamento dinâmico para evitar cache)
    $style_ver = file_exists($theme_dir . '/style.css') ? filemtime($theme_dir . '/style.css') : '1.0.2';
    wp_enqueue_style( 'flatsome-child-style', get_stylesheet_uri(), array( 'remixicon', 'jetbrains-mono' ), $style_ver );

    // CSS customizado (O visual moderno) - Carrega globalmente para manter a consistência visual
    $css_ver = file_exists($theme_dir . '/assets/css/custom-style.css') ? filemtime($theme_dir . '/assets/css/custom-style.css') : '1.0';
    wp_enqueue_style('custom-modern-style', $theme_uri . '/assets/css/custom-style.css', array(), $css_ver);

    // CSS isolado do Player de Áudio Global e Playlist Drawer
    $player_css_ver = file_exists($theme_dir . '/assets/css/player.css') ? filemtime($theme_dir . '/assets/css/player.css') : '1.0';
    wp_enqueue_style('centralmidi-player-style', $theme_uri . '/assets/css/player.css', array('flatsome-child-style'), $player_css_ver);

    // Howler.js para áudio
    wp_enqueue_script('howlerjs', $theme_uri . '/assets/js/howler.min.js', array(), '2.2.3', true);
    
    // Script customizado (áudio, busca moderna e interações globais)
    $js_ver = file_exists($theme_dir . '/assets/js/custom-script.js') ? filemtime($theme_dir . '/assets/js/custom-script.js') : '1.0';
    wp_enqueue_script('custom-script', $theme_uri . '/assets/js/custom-script.js', array('howlerjs', 'jquery'), $js_ver, true);

    // Cart manager (vanilla JS - alta performance, SEM jQuery)
    $cart_ver = file_exists($theme_dir . '/assets/js/cart-manager.js') ? filemtime($theme_dir . '/assets/js/cart-manager.js') : '1.0';
    wp_enqueue_script('cart-manager', $theme_uri . '/assets/js/cart-manager.js', array(), $cart_ver, true);

    // Localização de dados para o JS (Lógica do carrinho e busca)
    $cart_product_ids = array();
    if ( function_exists('WC') && isset(WC()->cart) && WC()->cart instanceof WC_Cart && WC()->cart->get_cart() ) {
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $cart_product_ids[] = (int) $cart_item['product_id'];
        }
    }
    
    $ajax_nonce = wp_create_nonce('cmidi_ajax_nonce');
    
    wp_localize_script('custom-script', 'centralMidiCart', array(
        'cartProductIds' => $cart_product_ids,
        'inCartText'     => 'No carrinho',
        'cartUrl'        => (function_exists('wc_get_cart_url') ? wc_get_cart_url() : ''),
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'nonce'         => $ajax_nonce,
        'homeUrl'        => home_url('/'),
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_child_theme_style_and_scripts');

// 4. AJAX Handler para segurança (verifica nonce)
add_action('wp_ajax_cmidi_add_to_cart', 'cmidi_ajax_add_to_cart');
add_action('wp_ajax_nopriv_cmidi_add_to_cart', 'cmidi_ajax_add_to_cart');
function cmidi_ajax_add_to_cart() {
    check_ajax_referer('cmidi_ajax_nonce', 'nonce');
    
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    
    if (!$product_id) {
        wp_send_json_error(array('message' => 'Product ID inválido'));
    }
    
    WC()->cart->add_to_cart($product_id);
    wp_send_json_success();
}

// 3. Homepage Shortcode (Destaques do Mês) com Cache (Transient) e Visual Moderno
add_shortcode('get_featured', 'query_music_featured');
/**
 * Aqueca os caches de post, meta e termos de uma lista de produtos.
 *
 * Sem isso, wc_get_product() dispara uma consulta de post, uma de meta e as de
 * product_cat para cada card da grade. Com o priming, a home e a busca consultam
 * o banco uma vez só por página.
 *
 * @param int[] $product_ids
 */
function cmidi_prime_cards($product_ids) {
    $ids = array_values(array_unique(array_filter(array_map('absint', (array) $product_ids))));
    if (!$ids || !function_exists('_prime_post_caches')) {
        return;
    }
    foreach (array_chunk($ids, 200) as $chunk) {
        _prime_post_caches($chunk, true, true);
    }
}

function query_music_featured() {
    $cached = get_transient('cmidi_featured_output_v24');
    if ($cached !== false) return $cached;

    $cm_db_available = class_exists('CentralMidi_DB');
    $ano_atual = (int) date('Y');
    $mes_atual = (int) date('n');
    $db_meses = $cm_db_available ? CentralMidi_DB::get_meses_por_ano($ano_atual) : array();

    $featured_months = array(
        0 => array('badge' => 'Lançamentos do Mês', 'icon' => 'ri-fire-fill', 'subtitle' => 'Músicas recém-adicionadas e novidades para o seu repertório.'),
        1 => array('badge' => 'Lançamentos do Mês Anterior', 'icon' => 'ri-sparkling-2-fill', 'subtitle' => 'Sucessos lançados no mês passado disponíveis para download.'),
        2 => array('badge' => 'Lançamentos Recentes', 'icon' => 'ri-box-3-fill', 'subtitle' => 'Confira também as faixas lançadas há 2 meses.'),
    );

    foreach ($featured_months as $i => $config) {
        if (isset($db_meses[$i])) {
            $mes = (int) $db_meses[$i];
            $ano = $ano_atual;
        } else {
            $total = ($ano_atual * 12) + ($mes_atual - 1) - $i;
            $mes = ($total % 12) + 1;
            $ano = (int) intdiv($total, 12);
        }
        $featured_months[$i]['mes'] = $mes;
        $featured_months[$i]['ano'] = $ano;
    }

    $month_data = array();
    foreach ($featured_months as $month_config) {
        $mes_num = $month_config['mes'];
        $ano_num = $month_config['ano'];
        $total_mes = $cm_db_available ? CentralMidi_DB::count_by_month($mes_num, $ano_num) : 0;
        $product_ids = $cm_db_available ? CentralMidi_DB::get_midis_by_month($mes_num, $ano_num, 24) : array();

        $month_data[] = array(
            'mes'          => $mes_num,
            'ano'          => $ano_num,
            'mes_nome'     => $cm_db_available ? CentralMidi_DB::mes_nome($mes_num) : '',
            'total'        => $total_mes,
            'product_ids'  => $product_ids,
            'badge'        => $month_config['badge'],
            'icon'         => $month_config['icon'],
            'subtitle'     => $month_config['subtitle'],
        );
    }

    $catalog_url = $cm_db_available && method_exists('CentralMidi_DB', 'catalog_url') ? CentralMidi_DB::catalog_url() : home_url('/midis/');

    // Uma única chamada em lote para os 3 meses: evita N+1 nos cards.
    $todos_os_ids = array();
    foreach ($month_data as $release) {
        $todos_os_ids = array_merge($todos_os_ids, $release['product_ids']);
    }
    cmidi_prime_cards($todos_os_ids);
    $midi_map = $cm_db_available ? CentralMidi_DB::get_midis_by_products($todos_os_ids) : array();

    ob_start();
    ?>
    <div class="cm-home-releases-wrapper">
        <?php foreach ($month_data as $index => $release) : ?>
            <section class="cm-month-section<?php echo ($index > 0) ? ' cm-month-section--bordered' : ''; ?>">
                <div class="cm-section-header">
                    <div class="cm-section-title-wrap">
                        <span class="cm-badge"><i class="<?php echo esc_attr($release['icon']); ?>"></i> <?php echo esc_html($release['badge']); ?></span>
                        <h2 class="cm-section-title">Lançamentos de <?php echo esc_html($release['mes_nome']); ?> <?php echo esc_html($release['ano']); ?></h2>
                        <p class="cm-section-subtitle"><?php echo esc_html($release['subtitle']); ?></p>
                    </div>

                    <div class="cm-section-actions">
                        <?php if (!empty($release['product_ids'])) : ?>
                            <button type="button" class="cm-btn cm-btn-primary cm-play-monthly-playlist" title="Reproduzir todas as músicas de <?php echo esc_attr($release['mes_nome']); ?>">
                                <i class="ri-play-list-2-fill"></i> Reproduzir Lista
                            </button>
                        <?php endif; ?>
                        <?php 
                        $meses_slug_map = array(1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr', 5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez');
                        $term_slug = isset($meses_slug_map[$release['mes']]) ? $meses_slug_map[$release['mes']] . '-' . $release['ano'] : '';
                        $month_term = $term_slug ? get_term_by('slug', $term_slug, 'mes_de_lancamento') : null;
                        $view_all_url = ($month_term && !is_wp_error($month_term)) ? get_term_link($month_term) : add_query_arg('mes_lancamento', $release['mes'], $catalog_url);
                        ?>
                        <a href="<?php echo esc_url($view_all_url); ?>" class="cm-btn cm-btn-outline cm-btn-view-all">
                            Ver todos de <?php echo esc_html($release['mes_nome']); ?> (<?php echo esc_html($release['total']); ?>) <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>
                </div>

                <?php if (!empty($release['product_ids'])) : ?>
                    <div class="cm-tracks-grid">
                        <?php foreach ($release['product_ids'] as $pid) : ?>
                            <?php get_template_part('template-parts/card-midi', null, array(
                                'product_id' => $pid,
                                'midi'       => isset($midi_map[$pid]) ? $midi_map[$pid] : null,
                            )); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="centralmidi-empty cm-empty-state">
                        <i class="ri-music-2-line"></i>
                        <p>Nenhum lançamento cadastrado para o mês de <?php echo esc_html($release['mes_nome']); ?> <?php echo esc_html($release['ano']); ?> ainda.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
    $output = ob_get_clean();
    set_transient('cmidi_featured_output_v21', $output, 600);
    return $output;
}

// 4. Customizações de Produtos e Loops (Otimizado com Caching)
// Removido hook redundante para evitar duplicidade de metadados nos cards
// add_action('woocommerce_shop_loop_item_title', 'cmidi_show_product_card_metadata', 20);
function cmidi_show_product_card_metadata() {
    global $product;
    if (!$product) return;
    
    $product_id = $product->get_id();
    
    // Limpa static array se atingir limite (previne memory leak)
    static $rendered_count = 0;
    static $rendered_ids = [];
    if ($rendered_count > 200) {
        $rendered_ids = array_slice($rendered_ids, -50);
        $rendered_count = count($rendered_ids);
    }
    if (in_array($product_id, $rendered_ids)) return;
    $rendered_ids[] = $product_id;
    $rendered_count++;

    // 1. Gênero Musical
    $cache_key_g = 'cmidi_genero_' . $product_id;
    $genero = wp_cache_get($cache_key_g, 'cmidi_terms');
    if ($genero === false) {
        $terms = get_the_terms($product_id, 'genero_musical');
        $genero = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
        wp_cache_set($cache_key_g, $genero, 'cmidi_terms', 3600);
    }
    
    echo '<div class="product-metadata-wrapper">';
    
    if($genero) {
        echo '<span class="genero-tag"><a href="' . get_term_link($genero) . '">' . esc_html($genero->name) . '</a></span>';
    }

    // 2. RLM (Letra/Melodia) - vem da tabela, não do postmeta
    $rlm_cache = 'cmidi_rlm_' . $product_id;
    $rlm = wp_cache_get($rlm_cache, 'cmidi_fields');
    if ($rlm === false) {
        $midi = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_midi_by_product($product_id) : null;
        $rlm = $midi ? $midi['classificacao'] : '';
        wp_cache_set($rlm_cache, $rlm, 'cmidi_fields', 3600);
    }
    if($rlm) {
        echo '<span class="rlm-tag">#' . esc_html($rlm) . '</span>';
    }
    
    // 3. Mês de Lançamento
    $mes_cache = 'cmidi_mes_' . $product_id;
    $mes = wp_cache_get($mes_cache, 'cmidi_terms');
    if ($mes === false) {
        $terms = get_the_terms($product_id, 'mes_de_lancamento');
        $mes = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
        wp_cache_set($mes_cache, $mes, 'cmidi_terms', 3600);
    }
    
    if($mes) {
        echo '<span class="data-tag">Lançamento: <strong>' . esc_html($mes->name) . '</strong></span>';
    }

    echo '</div>';
}

add_filter('woocommerce_order_button_text', function() { return 'Finalizar pedido'; });
remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);

// 6. OTIMIZAÇÃO DE BUSCA - Cache + Limite
add_action('pre_get_posts', 'cmidi_optimize_search_query');
function cmidi_optimize_search_query($query) {
    if (!is_admin() && $query->is_search() && $query->is_post_type('product')) {
        // LIMIT 30 resultados
        $query->set('posts_per_page', 30);
        
        // Busca apenas em título (mais rápido que LIKE em todo conteúdo)
        $query->set('search_fields', array('post_title'));
        
        // Performance: não contar founded rows desnecessárias
        $query->set('no_found_rows', true);
        
        // Cache de transient para resultados
        $s = get_search_query();
        if ($s) {
            $cache_key = 'cmidi_search_' . md5($s);
            $cached = get_transient($cache_key);
            if ($cached === false) {
                // Não faz nada aqui - o cache será populado após a query executar
            }
        }
    }
}

// 5. Muda o texto de 'Adicionar ao carrinho' para 'Comprar' globalmente
add_filter('woocommerce_product_add_to_cart_text', 'cmidi_custom_add_to_cart_text');
add_filter('woocommerce_product_single_add_to_cart_text', 'cmidi_custom_add_to_cart_text');
function cmidi_custom_add_to_cart_text() {
    return 'Comprar';
}

// Player de áudio na página individual do produto
add_action('woocommerce_single_product_summary', 'cmidi_single_product_audio_player', 15);
function cmidi_single_product_audio_player() {
    global $product;
    if (!$product) return;

    $midi = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_midi_by_product($product->get_id()) : null;
    $audio = $midi ? $midi['demo_url'] : '';
    if (!$audio) return;

    $player_id = 'player-demo-' . $product->get_id();
    echo '<div class="single-product-demo">';
    echo '<div class="demonstracao">';
    echo '<span class="button-audio button-play" onclick="playAudio(\'' . esc_url($audio) . '\', \'' . $player_id . '\', this);">';
    echo '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><polygon points="6,4 14,10 6,16"/></svg>';
    echo '</span>';
    echo '<div id="' . $player_id . '" class="player-container">';
    echo '<div class="player-timeline"><div class="player-progress"><div class="player-progress-fill"></div></div></div>';
    echo '</div></div></div>';
}

// Aviso de classificação RLM na página do produto
add_action('woocommerce_single_product_summary', 'cmidi_rlm_notice', 18);
function cmidi_rlm_notice() {
    echo '<div class="rlm-notice">';
    echo '<strong>Classificação #RLM</strong><br>';
    echo '#M = Midis somente com Melodia<br>';
    echo '#L = Midis somente com Letra sincronizada<br>';
    echo '#RLM = Midis com Melodia e Letra sincronizada<br>';
    echo 'Caso não haja essa classificação, considere portanto que o midi não tem melodia nem Letra!';
    echo '</div>';
}

// 6. Corrige paginação e limite de 20 por página para taxonomias (mes_de_lancamento, genero_musical)
add_action('init', 'cmidi_taxonomy_pagination_fix');
function cmidi_taxonomy_pagination_fix() {
    add_rewrite_rule('^mes_de_lancamento/([^/]+)/page/([0-9]+)/?$', 'index.php?mes_de_lancamento=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^genero_musical/([^/]+)/page/([0-9]+)/?$', 'index.php?genero_musical=$matches[1]&paged=$matches[2]', 'top');
}

add_action('pre_get_posts', 'cmidi_taxonomy_posts_per_page');
function cmidi_taxonomy_posts_per_page($query) {
    if (!is_admin() && $query->is_main_query()) {
        if ($query->is_tax('mes_de_lancamento') || $query->is_tax('genero_musical')) {
            $query->set('posts_per_page', 20);
            $query->set('cmidi_order_by_song', true);
        }
    }
}

// Ordena especificamente pelo NOME DA MÚSICA (parte após a barra '/')
add_filter('posts_orderby', 'cmidi_sort_by_song_title', 20, 2);
function cmidi_sort_by_song_title($orderby, $query) {
    global $wpdb;
    if ($query->get('cmidi_order_by_song')) {
        // Separa o título após a primeira barra e ordena alfabeticamente pelo nome da música
        $order = strtoupper($query->get('order')) === 'DESC' ? 'DESC' : 'ASC';
        return "TRIM(IF(LOCATE('/', {$wpdb->posts}.post_title) > 0, SUBSTRING({$wpdb->posts}.post_title, LOCATE('/', {$wpdb->posts}.post_title) + 1), {$wpdb->posts}.post_title)) {$order}";
    }
    return $orderby;
}

// 6.1 AJAX para Scroll Infinito (carrega de 20 em 20)
add_action('wp_ajax_cmidi_load_more_tracks', 'cmidi_ajax_load_more_tracks');
add_action('wp_ajax_nopriv_cmidi_load_more_tracks', 'cmidi_ajax_load_more_tracks');
function cmidi_ajax_load_more_tracks() {
    $tax   = isset($_POST['taxonomy']) ? sanitize_key($_POST['taxonomy']) : '';
    $term  = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
    $page  = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

    if (!wp_verify_nonce($nonce, 'cmidi_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }

    if (!in_array($tax, array('mes_de_lancamento', 'genero_musical'), true) || empty($term)) {
        wp_send_json_error(array('message' => 'Parâmetros inválidos'));
    }

    $args = array(
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 20,
        'paged'               => $page,
        'cmidi_order_by_song' => true,
        'tax_query'           => array(
            array(
                'taxonomy' => $tax,
                'field'    => 'slug',
                'terms'    => $term,
            ),
        ),
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        wp_send_json_success(array('html' => '', 'has_more' => false));
    }

    $post_ids = array();
    foreach ($query->posts as $p) {
        $post_ids[] = (int)$p->ID;
    }

    $midi_map = ($post_ids && class_exists('CentralMidi_DB'))
        ? CentralMidi_DB::get_midis_by_products($post_ids)
        : array();

    if (function_exists('cmidi_prime_cards')) {
        cmidi_prime_cards($post_ids);
    }

    ob_start();
    while ($query->have_posts()) {
        $query->the_post();
        $pid = get_the_ID();
        get_template_part('template-parts/card-midi', null, array(
            'product_id' => $pid,
            'midi'       => isset($midi_map[$pid]) ? $midi_map[$pid] : null,
        ));
    }
    $html = ob_get_clean();
    wp_reset_postdata();

    $has_more = ($page < $query->max_num_pages);

    wp_send_json_success(array(
        'html'     => $html,
        'has_more' => $has_more,
        'next_page'=> $page + 1,
        'max_pages'=> $query->max_num_pages,
    ));
}

// 7. Listagem de MIDIS por Mês (Shortcode [midis_por_mes])
if (!function_exists('f_midis_por_mes')) {
    add_shortcode('midis_por_mes', 'f_midis_por_mes');
    function f_midis_por_mes() {
        // Limpa cache se houver alteração ou parâmetro de refresh, ou se for admin
        if (isset($_GET['refresh_cache']) || current_user_can('manage_options')) {
            delete_transient('midis_por_mes_html_v2');
        }

        $cached = get_transient('midis_por_mes_html_v2');
        if ($cached !== false && !current_user_can('manage_options')) {
            return $cached;
        }

        $meses = [];
        $terms = get_terms( array( 
            'taxonomy' => 'mes_de_lancamento',
            'hide_empty' => true // Mostra apenas meses com produtos
        ));

        if (empty($terms) || is_wp_error($terms)) return '';

        foreach($terms as $term) {
            $meses[] = array($term->slug, $term->name, $term->count);
        }
        
        if (function_exists('compara_meses')) {
            usort( $meses, 'compara_meses' );
        }
        
    ob_start();
    echo '<div class="cm-hub-tax-wrapper">';
    echo '<div class="cm-hub-tax-grid cm-hub-meses-grid">';
    foreach($meses as $mes) {
        $url = get_term_link($mes[0], 'mes_de_lancamento');
        echo '<a href="' . esc_url($url) . '" class="cm-hub-card cm-hub-card-mes">
                <div class="cm-hub-icon-box">
                    <i class="ri-calendar-check-line"></i>
                </div>
                <div class="cm-hub-card-info">
                    <span class="cm-hub-card-title">' . esc_html($mes[1]) . '</span>
                    <span class="cm-hub-card-sub">Lançamentos</span>
                </div>
                <span class="cm-hub-count-badge">' . esc_html($mes[2]) . '</span>
              </a>';
    }
    echo '</div></div>';
    $output = ob_get_clean();
    
    set_transient('midis_por_mes_html_v2', $output, 1 * HOUR_IN_SECONDS);
    return $output;
  }
}

// Função para comparar meses cronologicamente (ex: JAN 2024 vs FEV 2024)
function compara_meses($a, $b) {
    $meses_map = array(
        'JAN' => '01', 'FEV' => '02', 'MAR' => '03', 'ABR' => '04', 'MAI' => '05', 'JUN' => '06',
        'JUL' => '07', 'AGO' => '08', 'SET' => '09', 'OUT' => '10', 'NOV' => '11', 'DEZ' => '12'
    );

    $parse_date = function($str) use ($meses_map) {
        $str = strtoupper(trim($str));
        $parts = preg_split('/[\s-]+/', $str);
        $m = '01';
        $y = '2000';

        foreach ($parts as $p) {
            $p_clean = substr($p, 0, 3);
            if (isset($meses_map[$p_clean])) {
                $m = $meses_map[$p_clean];
            } elseif (is_numeric($p)) {
                if (strlen($p) == 4) $y = $p;
                elseif (strlen($p) == 2) $y = ($p > 50 ? '19' : '20') . $p;
            }
        }
        return (int)($y . $m);
    };

    $dateA = $parse_date($a[1]);
    $dateB = $parse_date($b[1]);

    if ($dateA == $dateB) return 0;
    return ($dateA > $dateB) ? -1 : 1;
}

// 8. Listagem de MIDIS por Gênero (Shortcode [midis_por_genero])
if (!function_exists('f_midis_por_genero')) {
  add_shortcode('midis_por_genero', 'f_midis_por_genero');
  function f_midis_por_genero() {
    // Limpa cache se houver alteração ou parâmetro de refresh, ou se for admin
    if (isset($_GET['refresh_cache']) || current_user_can('manage_options')) {
      delete_transient('midis_por_genero_html_v2');
    }

    $cached = get_transient('midis_por_genero_html_v2');
    if ($cached !== false && !current_user_can('manage_options')) {
      return $cached;
    }

    $terms = get_terms( array( 
      'taxonomy' => 'genero_musical', 
      'orderby' => 'name', 
      'order' => 'ASC',
      'hide_empty' => false // Mostra TODOS os gêneros
    ));
        
    if (empty($terms) || is_wp_error($terms)) return '';

    ob_start();
    echo '<div class="cm-hub-tax-wrapper">';
    echo '<div class="cm-hub-tax-grid cm-hub-generos-grid">';
    foreach($terms as $term) {
      $url = get_term_link($term->slug, 'genero_musical');
      $clean_name = trim(str_replace('*', '', $term->name));
      echo '<a href="' . esc_url($url) . '" class="cm-hub-card cm-hub-card-genero">
              <div class="cm-hub-icon-box">
                  <i class="ri-disc-line"></i>
              </div>
              <div class="cm-hub-card-info">
                  <span class="cm-hub-card-title">' . esc_html($clean_name) . '</span>
                  <span class="cm-hub-card-sub">Gênero</span>
              </div>
              <span class="cm-hub-count-badge">' . esc_html($term->count) . '</span>
            </a>';
    }
    echo '</div></div>';
    $output = ob_get_clean();
        
    set_transient('midis_por_genero_html_v2', $output, 1 * HOUR_IN_SECONDS);
    return $output;
  }
}

// 9. Player Global de Áudio no Rodapé (Modern Sticky Player com Fila/Playlist)
add_action('wp_footer', 'cmidi_global_audio_player');
function cmidi_global_audio_player() {
    ?>
    <!-- Global Fixed Audio Player Bar -->
    <div id="cm-global-player" class="cm-player-bar hidden">
        <!-- Gaveta Retrátil de Playlist / Fila de Reprodução -->
        <div id="cm-playlist-drawer" class="cm-playlist-drawer" style="display: none;">
            <div class="cm-playlist-header">
                <div class="cm-playlist-title-wrap">
                    <i class="ri-play-list-2-fill"></i>
                    <h4>Fila de Reprodução (<span id="cm-playlist-count">0</span>)</h4>
                </div>
                <button type="button" id="cm-btn-close-playlist" class="cm-btn-icon" aria-label="Fechar lista">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div id="cm-playlist-items" class="cm-playlist-items">
                <!-- Itens da fila gerados via JavaScript -->
            </div>
        </div>

        <div class="cm-container cm-player-inner">
            <!-- Esquerda: Info da Faixa e Capa -->
            <div class="cm-player-track-info">
                <div class="cm-player-cover" id="cm-player-cover">
                    <img id="cm-player-thumb-img" src="" alt="" style="display: none;">
                    <div class="cm-player-thumb-placeholder" id="cm-player-thumb-placeholder">
                        <i class="ri-disc-line ri-spin"></i>
                    </div>
                </div>
                <div class="cm-player-details">
                    <div class="cm-player-title" id="cm-player-title">Nenhuma faixa selecionada</div>
                    <div class="cm-player-artist" id="cm-player-artist">-</div>
                </div>
            </div>

            <!-- Centro: Controles e Timeline -->
            <div class="cm-player-controls-center">
                <div class="cm-player-buttons">
                    <button type="button" class="cm-player-btn" id="cm-btn-prev" title="Faixa anterior" aria-label="Faixa anterior">
                        <i class="ri-skip-back-fill"></i>
                    </button>
                    <button type="button" class="cm-player-btn cm-btn-play-pause" id="cm-btn-main-play" title="Tocar / Pausar" aria-label="Tocar / Pausar">
                        <i class="ri-play-fill" id="cm-main-play-icon"></i>
                    </button>
                    <button type="button" class="cm-player-btn" id="cm-btn-next" title="Próxima faixa" aria-label="Próxima faixa">
                        <i class="ri-skip-forward-fill"></i>
                    </button>
                    <button type="button" class="cm-player-btn" id="cm-btn-stop" title="Parar" aria-label="Parar">
                        <i class="ri-stop-fill"></i>
                    </button>
                </div>
                <div class="cm-player-timeline">
                    <span class="cm-time" id="cm-current-time">00:00</span>
                    <div class="cm-progress-container" id="cm-progress-bar">
                        <div class="cm-progress-fill" id="cm-progress-fill"></div>
                    </div>
                    <span class="cm-time" id="cm-duration-time">00:00</span>
                </div>
            </div>

            <!-- Direita: Ações, Volume, Playlist e Fechar -->
            <div class="cm-player-actions">
                <a id="cm-player-buy-link" href="#" class="cm-btn cm-btn-primary cm-player-buy add_to_cart_button ajax_add_to_cart" hidden>
                    <i class="ri-shopping-cart-line"></i> <span>Comprar</span>
                </a>

                <button type="button" id="cm-btn-toggle-playlist" class="cm-player-btn cm-btn-playlist-toggle" title="Ver fila de reprodução" aria-label="Ver fila de reprodução">
                    <i class="ri-play-list-line"></i>
                    <span id="cm-playlist-badge" class="cm-playlist-badge" style="display: none;">0</span>
                </button>

                <div class="cm-volume-wrapper">
                    <i class="ri-volume-up-line" id="cm-volume-icon"></i>
                    <label class="cm-visually-hidden" for="cm-volume-slider">Volume</label>
                    <input type="range" min="0" max="1" step="0.05" value="0.8" id="cm-volume-slider" class="cm-volume-slider">
                </div>

                <button type="button" id="cm-btn-close-player" class="cm-btn-icon" title="Fechar Player" aria-label="Fechar player">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Elemento HTML5 Audio Nativo -->
    <audio id="cm-audio-element" preload="none"></audio>
    <?php
}

// === ADMIN TOOLS (sem tabela) ===

function cmidi_sanitize_classificacao($value) {
    if (class_exists('CentralMidi_DB') && is_callable(array('CentralMidi_DB', 'sanitize_classificacao'))) {
        return call_user_func(array('CentralMidi_DB', 'sanitize_classificacao'), $value);
    }
    $value = strtoupper(trim((string) $value));
    return in_array($value, array('M', 'L', 'RLM'), true) ? $value : '';
}

function cmidi_sync_tabela_classificacao($product_id, $class) {
    if (!class_exists('CentralMidi_DB') || !is_callable(array('CentralMidi_DB', 'table_name'))) return;
    global $wpdb;
    $table = call_user_func(array('CentralMidi_DB', 'table_name'));
    $wpdb->update(
        $table,
        array('classificacao' => $class, 'updated_at' => current_time('mysql')),
        array('product_id' => (int) $product_id),
        array('%s', '%s'),
        array('%d')
    );
    if (is_callable(array('CentralMidi_DB', 'flush_midi_cache'))) {
        CentralMidi_DB::flush_midi_cache();
    }
}

function cmidi_primeiro_termo($term_ids, $taxonomy) {
    if (empty($term_ids)) return null;
    $term = get_term((int)reset($term_ids), $taxonomy);
    return ($term && !is_wp_error($term)) ? $term : null;
}

function cmidi_sync_tabela_colunas($product_id, $colunas) {
    if (!class_exists('CentralMidi_DB') || !is_callable(array('CentralMidi_DB', 'table_name'))) return;
    if (empty($colunas)) return;
    global $wpdb;

    $table = preg_replace('/[^A-Za-z0-9_]/', '', call_user_func(array('CentralMidi_DB', 'table_name')));
    if (!$table) return;

    $sets = array(); $vals = array();
    foreach ($colunas as $col => $val) {
        $col = preg_replace('/[^A-Za-z0-9_]/', '', $col);
        if (!$col) continue;
        $sets[] = "`$col` = %d";
        $vals[] = (int)$val;
    }
    if (empty($sets)) return;

    $vals[] = (int)$product_id;
    $wpdb->query($wpdb->prepare("UPDATE `$table` SET " . implode(', ', $sets) . " WHERE product_id = %d", $vals));

    // A lista de artistas vem de product_cat: mudar termo/coluna invalida o cache.
    if (is_callable(array('CentralMidi_DB', 'flush_midi_cache'))) {
        CentralMidi_DB::flush_midi_cache();
    }
}

function cmidi_mes_ano_do_termo($term) {
    $meses = array('jan' => 1, 'fev' => 2, 'mar' => 3, 'abr' => 4, 'mai' => 5, 'jun' => 6,
                   'jul' => 7, 'ago' => 8, 'set' => 9, 'out' => 10, 'nov' => 11, 'dez' => 12);
    if (!$term) return array(0, 0);

    foreach (array($term->slug, $term->name) as $src) {
        $src = ltrim(trim((string)$src), ',');
        if (preg_match('/^([a-zA-Z]{3,4})[\s\-\/]*(19|20)(\d{2})$/', $src, $m)) {
            $ini = strtolower(substr($m[1], 0, 3));
            if (isset($meses[$ini])) return array($meses[$ini], (int)($m[2] . $m[3]));
        }
    }
    return array(0, 0);
}

function cmidi_sync_artista_meta($product_id, $term) {
    $nome   = ($term && !empty($term->name)) ? $term->name : '';
    $ref_id = 0;
    if ($nome !== '' && class_exists('CentralMidi_DB') && is_callable(array('CentralMidi_DB', 'get_artista_by_nome'))) {
        $reg = call_user_func(array('CentralMidi_DB', 'get_artista_by_nome'), $nome);
        if ($reg && isset($reg->id)) $ref_id = (int)$reg->id;
    }

    // Só a TABELA é a fonte de verdade. Nada de postmeta.
    cmidi_sync_tabela_colunas($product_id, array('artista_id' => $ref_id));
}

function cmidi_sync_genero_meta($product_id, $term) {
    $nome   = ($term && !empty($term->name)) ? $term->name : '';
    $ref_id = 0;
    if ($nome !== '' && class_exists('CentralMidi_DB') && is_callable(array('CentralMidi_DB', 'get_genero_by_nome'))) {
        $reg = call_user_func(array('CentralMidi_DB', 'get_genero_by_nome'), $nome);
        if ($reg && isset($reg->id)) $ref_id = (int)$reg->id;
    }

    cmidi_sync_tabela_colunas($product_id, array('genero_id' => $ref_id));
}

function cmidi_sync_mes_meta($product_id, $term) {
    if (!$term || empty($term->name)) {
        cmidi_sync_tabela_colunas($product_id, array('mes_lancamento' => 0, 'ano_lancamento' => 0));
        return;
    }
    list($mes, $ano) = cmidi_mes_ano_do_termo($term);
    if ($mes <= 0) return; // formato desconhecido: nao apaga o que existe

    cmidi_sync_tabela_colunas($product_id, array('mes_lancamento' => $mes, 'ano_lancamento' => $ano));
}

add_action('wp_ajax_pa_admin_stats', 'pa_admin_stats');
function pa_admin_stats() {
    check_ajax_referer('pa_admin_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Acesso negado');

    $stats = get_transient('pa_admin_stats_cache');
    if (false === $stats) {
        global $wpdb;
        $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'");
        $with_demo = class_exists('CentralMidi_DB') ? CentralMidi_DB::count_products_with_demo() : 0;
        $stats = array('total' => $total, 'withDemo' => $with_demo);
        set_transient('pa_admin_stats_cache', $stats, 300);
    }
    wp_send_json_success(array('results' => array('stats' => $stats)));
}

add_action('wp_ajax_pa_admin_get_taxonomy_terms', 'pa_admin_get_taxonomy_terms');
function pa_admin_get_taxonomy_terms() {
    check_ajax_referer('pa_admin_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Acesso negado');
    $tax = isset($_POST['taxonomy']) ? sanitize_key($_POST['taxonomy']) : '';
    if (!in_array($tax, array('genero_musical', 'mes_de_lancamento', 'tipo', 'product_cat'))) wp_send_json_error('Taxonomia inválida');
    $terms = get_terms(array('taxonomy' => $tax, 'hide_empty' => false));
    $result = array();
    foreach ($terms as $t) {
        $result[] = array('id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug);
    }
    wp_send_json_success($result);
}

add_action('wp_ajax_pa_admin_list_products', 'pa_admin_list_products');
function pa_admin_list_products() {
    check_ajax_referer('pa_admin_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Acesso negado');

    $page   = isset($_POST['page'])   ? max(1, (int)$_POST['page']) : 1;
    $limit  = isset($_POST['limit'])  ? min(100, (int)$_POST['limit']) : 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';

    $search_id = isset($_POST['search_id']) ? (int)$_POST['search_id'] : 0;

    $filtro_genero  = isset($_POST['filtro_genero'])  ? (array)$_POST['filtro_genero'] : array();
    $filtro_mes     = isset($_POST['filtro_mes'])     ? (array)$_POST['filtro_mes'] : array();
    $filtro_tipo    = isset($_POST['filtro_tipo'])    ? (array)$_POST['filtro_tipo'] : array();
    $filtro_artista = isset($_POST['filtro_artista']) ? (array)$_POST['filtro_artista'] : array();
    $filtro_demo    = isset($_POST['filtro_demo'])    ? $_POST['filtro_demo'] : 'all';
    $filtro_demo_search = isset($_POST['filtro_demo_search']) ? trim($_POST['filtro_demo_search']) : '';

    // Normalize: remove empty string values from single-select dropdowns
    $filtro_genero  = array_filter($filtro_genero);
    $filtro_mes     = array_filter($filtro_mes);
    $filtro_tipo    = array_filter($filtro_tipo);
    $filtro_artista = array_filter($filtro_artista);

    global $wpdb;

    $join  = "";
    $where = "p.post_type='product' AND p.post_status='publish'";

    if ($search_id > 0) {
        $where .= $wpdb->prepare(" AND p.ID = %d", $search_id);
    } else {
        // Get matching IDs from taxonomy filters first (much faster than subqueries)
        $all_tax_filters = array();
        if (!empty($filtro_genero))  $all_tax_filters['genero_musical'] = array_map('intval', $filtro_genero);
        if (!empty($filtro_mes))     $all_tax_filters['mes_de_lancamento'] = array_map('intval', $filtro_mes);
        if (!empty($filtro_tipo))    $all_tax_filters['tipo'] = array_map('intval', $filtro_tipo);
        if (!empty($filtro_artista)) $all_tax_filters['product_cat'] = array_map('intval', $filtro_artista);

        $tax_match_ids = null;
        foreach ($all_tax_filters as $tax => $term_ids) {
            $ids = get_objects_in_term($term_ids, $tax);
            if ($tax_match_ids === null) {
                $tax_match_ids = $ids;
            } else {
                $tax_match_ids = array_intersect($tax_match_ids, $ids);
            }
            if (empty($tax_match_ids)) break;
        }

        if ($tax_match_ids !== null) {
            if (empty($tax_match_ids)) {
                $where .= " AND 1=0";
            } else {
                $where .= " AND p.ID IN (" . implode(',', $tax_match_ids) . ")";
            }
        }

        if ($search) {
            $where .= $wpdb->prepare(" AND p.post_title LIKE %s", '%' . $wpdb->esc_like($search) . '%');
        }

        // O filtro de demo mora no plugin: a chave de postmeta não aparece no child.
        CentralMidi_DB::apply_demo_filter($filtro_demo, $filtro_demo_search, $join, $where);
    }

    $total    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p $join WHERE $where");
    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title,
            (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_regular_price' ORDER BY meta_id ASC LIMIT 1) as regular_price,
            (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_thumbnail_id' ORDER BY meta_id ASC LIMIT 1) as thumb_id
         FROM {$wpdb->posts} p
         $join
         WHERE $where ORDER BY p.ID DESC LIMIT %d OFFSET %d",
        $limit, $offset
    ));

    $results = array();
    if (!empty($products)) {
        $ids      = wp_list_pluck($products, 'ID');
        $ids_flat = implode(',', array_map('intval', $ids));

        // Gênero/mês/tipo continuam vindo das taxonomias (o admin filtra por term_id).
        // Os artistas vêm do product_cat, já lidos em lote pela CentralMidi_DB.
        $term_rows = $wpdb->get_results(
            "SELECT tr.object_id, t.term_id, t.name, tt.taxonomy
             FROM {$wpdb->term_relationships} tr
             JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE tr.object_id IN ($ids_flat)
             AND tt.taxonomy IN ('genero_musical','mes_de_lancamento','tipo')"
        );

        $terms_by_post = array();
        foreach ($term_rows as $row) {
            $pid = $row->object_id;
            $tax = $row->taxonomy;
            if (!isset($terms_by_post[$pid])) $terms_by_post[$pid] = array();
            if (!isset($terms_by_post[$pid][$tax])) $terms_by_post[$pid][$tax] = array();
            $terms_by_post[$pid][$tax][] = $row;
        }

        $midi_map = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_midis_by_products($ids) : array();

        foreach ($products as $p) {
            $pid = $p->ID;
            $pt = isset($terms_by_post[$pid]) ? $terms_by_post[$pid] : array();
            $midi = isset($midi_map[$pid]) ? $midi_map[$pid] : array();

            $artistas   = isset($midi['artistas']) ? $midi['artistas'] : array();
            $artista_ids = isset($midi['artista_ids']) ? $midi['artista_ids'] : array();
            $generos    = array();
            $tipos      = array();
            $meses      = array();
            $genero_ids = array();
            $mes_ids    = array();
            $tipo_ids   = array();

            if (isset($pt['genero_musical'])) {
                foreach ($pt['genero_musical'] as $t) { $generos[] = $t->name; $genero_ids[] = (int)$t->term_id; }
            }
            if (isset($pt['mes_de_lancamento'])) {
                foreach ($pt['mes_de_lancamento'] as $t) { $meses[] = $t->name; $mes_ids[] = (int)$t->term_id; }
            }
            if (isset($pt['tipo'])) {
                foreach ($pt['tipo'] as $t) { $tipos[] = $t->name; $tipo_ids[] = (int)$t->term_id; }
            }

            // Artista principal: nome vindo de midis.artista_id (tabela), sem postmeta.
            $artista_principal = isset($midi['artista_nome']) ? $midi['artista_nome'] : '';
            if ($artista_principal !== '' && count($artista_ids) > 1) {
                $idx = array_search($artista_principal, $artistas, true);
                if ($idx !== false && $idx > 0) {
                    $id_principal  = $artista_ids[$idx];
                    $nome_principal = $artistas[$idx];
                    unset($artista_ids[$idx], $artistas[$idx]);
                    array_unshift($artista_ids, $id_principal);
                    array_unshift($artistas, $nome_principal);
                    $artista_ids = array_values($artista_ids);
                    $artistas    = array_values($artistas);
                }
            }

            $url_demo = isset($midi['demo_raw']) ? $midi['demo_raw'] : '';
            $rlm      = isset($midi['classificacao']) ? $midi['classificacao'] : '';
            $thumb    = $p->thumb_id ? wp_get_attachment_image_url($p->thumb_id, array(40, 40)) : '';

            $results[] = array(
                'id'       => $pid,
                'title'    => $p->post_title,
                'artista'  => $artistas,
                'artista_ids' => $artista_ids,
                'generos'  => $generos,
                'meses'    => $meses,
                'tipos'    => $tipos,
                'genero_ids'  => $genero_ids,
                'mes_ids'     => $mes_ids,
                'tipo_ids'    => $tipo_ids,
                'preco'    => $p->regular_price ? number_format((float)$p->regular_price, 2, '.', '') : '',
                'url_demo' => $url_demo,
                'rlm'      => $rlm,
                'thumb'    => $thumb,
                'edit_url' => admin_url('post.php?post=' . $pid . '&action=edit'),
            );
        }
    }

    wp_send_json_success(array(
        'total'       => $total,
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => max(1, ceil($total / $limit)),
        'results'     => $results,
    ));
}

add_action('wp_ajax_pa_admin_save_product', 'pa_admin_save_product');
function pa_admin_save_product() {
    check_ajax_referer('pa_admin_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Acesso negado');

    delete_transient('pa_admin_stats_cache');

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if (!$product_id) wp_send_json_error('ID inválido');

    $field = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
    $value = isset($_POST['value']) ? $_POST['value'] : '';

    switch ($field) {
        case 'url_demo':
            CentralMidi_DB::set_demo_audio($product_id, esc_url_raw($value));
            delete_post_meta($product_id, 'url_demo');
            break;
        case 'rlm':
            $rlm_norm = cmidi_sanitize_classificacao($value);
            delete_post_meta($product_id, 'rlm');
            cmidi_sync_tabela_classificacao($product_id, $rlm_norm);
            break;
        case 'genero_musical':
            $terms = array_map('intval', (array)$value);
            $terms = array_filter($terms);
            wp_set_post_terms($product_id, $terms, 'genero_musical');
            cmidi_sync_genero_meta($product_id, cmidi_primeiro_termo($terms, 'genero_musical'));
            break;
        case 'mes_de_lancamento':
            $terms = array_map('intval', (array)$value);
            $terms = array_filter($terms);
            wp_set_post_terms($product_id, $terms, 'mes_de_lancamento');
            cmidi_sync_mes_meta($product_id, cmidi_primeiro_termo($terms, 'mes_de_lancamento'));
            wp_cache_delete('cmidi_mes_' . $product_id, 'cmidi_terms');
            break;
        case 'tipo':
            $terms = array_map('intval', (array)$value);
            $terms = array_filter($terms);
            wp_set_post_terms($product_id, $terms, 'tipo');
            break;
        case 'product_cat':
            $terms = array_map('intval', (array)$value);
            $terms = array_filter($terms);
            wp_set_post_terms($product_id, $terms, 'product_cat');
            cmidi_sync_artista_meta($product_id, cmidi_primeiro_termo($terms, 'product_cat'));
            break;
        case '_regular_price':
            $price = sanitize_text_field($value); // Valor já deve vir com ponto
            $price = is_numeric($price) ? number_format((float)$price, 2, '.', '') : '';
            update_post_meta($product_id, '_regular_price', $price);
            update_post_meta($product_id, '_price', $price);
            if (function_exists('wc_delete_product_transients')) {
                wc_delete_product_transients($product_id);
            }
            break;
        case 'post_title':
            wp_update_post(array('ID' => $product_id, 'post_title' => sanitize_text_field($value)));
            break;
        default:
            wp_send_json_error('Campo inválido');
    }
    wp_send_json_success(array('message' => 'Salvo'));
}

add_action('wp_ajax_pa_admin_check_duplicates', 'pa_admin_check_duplicates');
add_action('wp_ajax_pa_admin_buscar_artistas', 'pa_admin_buscar_artistas');
add_action('wp_ajax_pa_admin_export_csv', 'pa_admin_export_csv');

function pa_admin_buscar_artistas() {
    check_ajax_referer('pa_admin_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Acesso negado');

    $termo = isset($_POST['termo']) ? sanitize_text_field(wp_unslash($_POST['termo'])) : '';
    if (mb_strlen($termo) < 2) wp_send_json_success(array());

    $achados = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'search'     => str_replace('"', '', $termo),
        'number'     => 50,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ));
    if (is_wp_error($achados)) wp_send_json_success(array());

    $out = array();
    foreach ($achados as $t) {
        $out[] = array('id' => (int)$t->term_id, 'name' => $t->name, 'count' => (int)$t->count);
    }
    wp_send_json_success($out);
}

function pa_admin_export_where($req, &$where, &$join) {
    global $wpdb;
    $join  = '';
    $where = "p.post_type='product' AND p.post_status='publish'";

    $search_id = isset($req['search_id']) ? (int)$req['search_id'] : 0;
    if ($search_id > 0) {
        $where .= $wpdb->prepare(" AND p.ID = %d", $search_id);
        return;
    }

    $filtros = array();
    foreach (array('filtro_genero' => 'genero_musical', 'filtro_mes' => 'mes_de_lancamento',
                   'filtro_tipo' => 'tipo', 'filtro_artista' => 'product_cat') as $k => $tax) {
        $v = isset($req[$k]) ? array_filter((array)$req[$k]) : array();
        if (!empty($v)) $filtros[$tax] = array_map('intval', $v);
    }

    $ids_match = null;
    foreach ($filtros as $tax => $term_ids) {
        $ids = get_objects_in_term($term_ids, $tax);
        $ids_match = ($ids_match === null) ? $ids : array_intersect($ids_match, $ids);
        if (empty($ids_match)) break;
    }
    if ($ids_match !== null) {
        if (empty($ids_match)) {
            $where .= " AND 1=0";
        } else {
            $where .= " AND p.ID IN (" . implode(',', $ids_match) . ")";
        }
    }

    if (!empty($req['search'])) {
        $where .= $wpdb->prepare(" AND p.post_title LIKE %s", '%' . $wpdb->esc_like(trim($req['search'])) . '%');
    }

    $demo = isset($req['filtro_demo']) ? $req['filtro_demo'] : 'all';
    $demo_search = !empty($req['filtro_demo_search']) ? trim($req['filtro_demo_search']) : '';

    CentralMidi_DB::apply_demo_filter($demo, $demo_search, $join, $where);
}

function pa_admin_export_rows($where, $join, $limit, $offset) {
    global $wpdb;

    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title,
                (SELECT rp.meta_value FROM {$wpdb->postmeta} rp
                  WHERE rp.post_id=p.ID AND rp.meta_key='_regular_price'
                  ORDER BY rp.meta_id DESC LIMIT 1) as regular_price
         FROM {$wpdb->posts} p
         $join
         WHERE $where ORDER BY p.ID DESC LIMIT %d OFFSET %d",
        $limit, $offset
    ));
    if (empty($products)) return array();

    $ids      = wp_list_pluck($products, 'ID');
    $ids_flat = implode(',', array_map('intval', $ids));

    $term_rows = $wpdb->get_results(
        "SELECT tr.object_id, t.name, tt.taxonomy
         FROM {$wpdb->term_relationships} tr
         JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
         JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
         WHERE tr.object_id IN ($ids_flat)
         AND tt.taxonomy IN ('genero_musical','mes_de_lancamento','tipo')"
    );
    $terms_by_post = array();
    foreach ($term_rows as $row) {
        if (!isset($terms_by_post[$row->object_id])) $terms_by_post[$row->object_id] = array();
        if (!isset($terms_by_post[$row->object_id][$row->taxonomy])) $terms_by_post[$row->object_id][$row->taxonomy] = array();
        $terms_by_post[$row->object_id][$row->taxonomy][] = $row->name;
    }

    $midi_map = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_midis_by_products($ids) : array();

    $linhas = array();
    foreach ($products as $p) {
        $pid = $p->ID;
        $pt  = isset($terms_by_post[$pid]) ? $terms_by_post[$pid] : array();
        $midi = isset($midi_map[$pid]) ? $midi_map[$pid] : array();
        $linhas[] = array(
            $pid,
            isset($midi['artistas'])        ? implode(', ', $midi['artistas'])        : '',
            $p->post_title,
            isset($midi['classificacao'])   ? $midi['classificacao']                : '',
            isset($pt['tipo'])              ? implode(', ', $pt['tipo'])              : '',
            $p->regular_price ? number_format((float)$p->regular_price, 2, '.', '') : '',
            isset($pt['genero_musical'])    ? implode(', ', $pt['genero_musical'])    : '',
            isset($pt['mes_de_lancamento']) ? implode(', ', $pt['mes_de_lancamento']) : '',
            isset($midi['demo_raw'])        ? $midi['demo_raw']                      : '',
        );
    }
    return $linhas;
}

function pa_admin_export_csv() {
    check_ajax_referer('pa_admin_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Acesso negado');

    global $wpdb;
    $where = ''; $join = '';
    pa_admin_export_where($_POST, $where, $join);
    $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p $join WHERE $where");

    while (ob_get_level() > 0) ob_end_clean();
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="centralmidi-produtos.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('ID', 'Artista', 'Musica', 'Classificacao', 'Tipo', 'Preco', 'Genero', 'Mes', 'URL Demo'), ';', '"', '\\');

    $fatia = 2000;
    for ($offset = 0; $offset < $total; $offset += $fatia) {
        $linhas = pa_admin_export_rows($where, $join, $fatia, $offset);
        if (empty($linhas)) break;
        foreach ($linhas as $l) {
            fputcsv($out, $l, ';', '"', '\\');
        }
        flush();
    }
    fclose($out);
    exit;
}
function pa_admin_check_duplicates() {
    check_ajax_referer('pa_admin_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Acesso negado');

    global $wpdb;

    $duplicates = array();

    // 1. Produtos com mesmo título
    $titulos = $wpdb->get_results(
        "SELECT p.ID, p.post_title
         FROM {$wpdb->posts} p
         JOIN (
             SELECT post_title
             FROM {$wpdb->posts}
             WHERE post_type='product' AND post_status='publish'
             GROUP BY post_title
             HAVING COUNT(*) > 1
         ) dup ON p.post_title = dup.post_title
         WHERE p.post_type='product' AND p.post_status='publish'
         ORDER BY p.post_title, p.ID"
    );
    foreach ((array)$titulos as $t) {
        $duplicates[] = array(
            'tipo'  => 'Título',
            'valor' => $t->post_title,
            'id'    => (int)$t->ID,
            'nome'  => $t->post_title,
            'url_demo' => '',
        );
    }

    // 2. Produtos com mesma URL de demo (leitura de postmeta isolada no plugin)
    $urls = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_duplicated_demo_urls() : array();
    foreach ((array)$urls as $u) {
        $duplicates[] = array(
            'tipo'  => 'URL Demo',
            'valor' => $u->meta_value,
            'id'    => (int)$u->post_id,
            'nome'  => $u->post_title,
            'url_demo' => $u->meta_value,
        );
    }

    wp_send_json_success($duplicates);
}

add_shortcode('admin_tools', function() {
    if (!current_user_can('manage_options')) return '<p>Acesso restrito.</p>';
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('pa_admin_nonce');
    ob_start();
    include get_stylesheet_directory() . '/page-admin-tools.php';
    return ob_get_clean();
});

// Botão Flutuante do WhatsApp no canto inferior direito
add_action('wp_footer', 'cmidi_floating_whatsapp_button', 25);
function cmidi_floating_whatsapp_button() {
    $phone = '5531984511174';
    $message = rawurlencode('Olá! Estou no site da Central MIDI e gostaria de tirar uma dúvida sobre os midis/playbacks.');
    $link = "https://api.whatsapp.com/send/?phone={$phone}&text={$message}&type=phone_number&app_absent=0";
    ?>
    <a href="<?php echo esc_url($link); ?>" 
       id="cmidi-whatsapp-float" 
       class="cmidi-whatsapp-float" 
       target="_blank" 
       rel="noopener noreferrer" 
       aria-label="Fale conosco pelo WhatsApp"
       title="Fale conosco pelo WhatsApp">
        <i class="ri-whatsapp-fill"></i>
        <span class="whatsapp-tooltip">Fale conosco no WhatsApp</span>
    </a>
    <?php
}

// Modal de Busca Moderna Central MIDI (substitui a lightbox antiga do tema)
add_action('wp_footer', 'cmidi_render_modern_search_modal', 30);
function cmidi_render_modern_search_modal() {
    ?>
    <div id="cm-search-overlay" class="cm-search-overlay" aria-hidden="true">
        <div class="cm-search-backdrop"></div>
        <div class="cm-search-modal-container">
            <div class="cm-search-modal" role="dialog" aria-modal="true" aria-label="Buscar MIDIs">
                <button type="button" class="cm-search-close-btn" id="cm-search-close" aria-label="Fechar busca">
                    <i class="ri-close-line"></i>
                </button>

                <div class="cm-search-header">
                    <span class="cm-search-badge"><i class="ri-search-2-line"></i> Busca Rápida</span>
                    <h2 class="cm-search-title">O que você quer tocar hoje?</h2>
                    <p class="cm-search-desc">Pesquise por nome da música, cantor, banda ou gênero.</p>
                </div>

                <form role="search" method="get" class="cm-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                    <div class="cm-search-input-wrap">
                        <i class="ri-search-line cm-search-icon"></i>
                        <input type="search" 
                               id="cm-search-input" 
                               class="cm-search-field" 
                               placeholder="Ex: Fim de Noite, Gusttavo Lima, Forró..." 
                               value="" 
                               name="s" 
                               autocomplete="off" 
                               spellcheck="false" 
                               required>
                        <input type="hidden" name="post_type" value="product">
                        <button type="button" class="cm-search-clear-btn" id="cm-search-clear" style="display:none;" aria-label="Limpar busca">
                            <i class="ri-close-circle-fill"></i>
                        </button>
                        <button type="submit" class="cm-search-submit-btn" aria-label="Pesquisar">
                            <span>Buscar</span>
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </div>
                </form>

                <!-- Container de Sugestões em Tempo Real (Live Search) -->
                <div id="cm-live-suggestions" class="cm-live-suggestions" style="display:none;"></div>

                <div class="cm-search-quick-tags" id="cm-search-quick-tags">
                    <span class="cm-quick-tags-label"><i class="ri-fire-line"></i> Mais buscados:</span>
                    <div class="cm-tags-list">
                        <a href="<?php echo esc_url(home_url('/?s=sertanejo&post_type=product')); ?>" class="cm-quick-tag">Sertanejo</a>
                        <a href="<?php echo esc_url(home_url('/?s=forro&post_type=product')); ?>" class="cm-quick-tag">Forró</a>
                        <a href="<?php echo esc_url(home_url('/?s=gospel&post_type=product')); ?>" class="cm-quick-tag">Gospel</a>
                        <a href="<?php echo esc_url(home_url('/?s=rock&post_type=product')); ?>" class="cm-quick-tag">Rock</a>
                        <a href="<?php echo esc_url(home_url('/?s=pagode&post_type=product')); ?>" class="cm-quick-tag">Pagode</a>
                        <a href="<?php echo esc_url(home_url('/?s=piseiro&post_type=product')); ?>" class="cm-quick-tag">Piseiro</a>
                    </div>
                </div>

                <div class="cm-search-tip">
                    <i class="ri-keyboard-line"></i> Pressione <kbd>ESC</kbd> para fechar ou <kbd>ENTER</kbd> para buscar.
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Limpa o título do MIDI para exibição na interface (PHP).
 * Preserva o post_title original no banco de dados.
 *
 * 1. Separa pelo primeiro '/' isolando o artista e mantendo o nome da música (incluindo medleys com mais barras).
 * 2. Remove sufixos técnicos legados (#RLM, #L, #M).
 * 3. Remove tags técnicas entre colchetes ([CM], [PRO], [DRM], etc.).
 */
function cmidi_clean_song_title($title) {
    if (!$title) {
        return '';
    }
    $song = $title;
    if (strpos($title, '/') !== false) {
        $parts = explode('/', $title, 2);
        $song = isset($parts[1]) ? trim($parts[1]) : $title;
    }
    // Remove sufixos de classificação legados (#RLM, #L, #M)
    $song = preg_replace('/#(RLM|L|M)\b/i', '', $song);
    // Remove tags técnicas entre colchetes ([CM], [PRO], etc.)
    $song = preg_replace('/\[[^\]]+\]/', '', $song);
    // Remove espaços extras
    $song = preg_replace('/\s+/', ' ', $song);
    return trim($song);
}

/**
 * ========================================================================
 * FOOTER MODERNO CENTRAL MIDI (CHILD THEME)
 * ========================================================================
 * O rodapé é renderizado por footer.php, no próprio child. Antes ele vinha de
 * flatsome/footer.php, que abria a casca e disparava `flatsome_footer`; aqui o
 * child desligava o `flatsome_page_footer` e se pendurava no mesmo hook.
 *
 * Com o template próprio, nada mais depende desse hook, e o
 * `cmidi_render_modern_footer` deixou de existir.
 *
 * Os dois remove_action abaixo são mantidos de propósito: são rede de segurança.
 * O tema pai e alguns plugins ainda podem disparar `flatsome_footer` por outros
 * caminhos, e sem eles o rodapé legado do Flatsome reapareceria ali.
 */
add_action('init', 'cmidi_setup_modern_footer', 20);
function cmidi_setup_modern_footer() {
    // Impede o rodapé e os ícones de pagamento legados do Flatsome de aparecerem
    // se algum outro template ainda disparar o hook.
    remove_action('flatsome_footer', 'flatsome_page_footer', 10);
    remove_action('flatsome_absolute_footer_secondary', 'flatsome_footer_payment_icons');
}

/**
 * ========================================================================
 * BUSCA RÁPIDA EM TEMPO REAL (AJAX LIVE SEARCH COM SUGESTÕES)
 * ========================================================================
 */
add_action('wp_ajax_cmidi_ajax_live_search', 'cmidi_ajax_live_search');
add_action('wp_ajax_nopriv_cmidi_ajax_live_search', 'cmidi_ajax_live_search');
function cmidi_ajax_live_search() {
    $term = isset($_GET['query']) ? sanitize_text_field(wp_unslash($_GET['query'])) : '';

    if (empty($term) || mb_strlen($term) < 2) {
        wp_send_json_success(array('results' => array(), 'total' => 0));
    }

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $term,
        'posts_per_page' => 8,
        'no_found_rows'  => false,
    );

    $query = new WP_Query($args);
    $results = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_the_ID();
            $product    = wc_get_product($product_id);
            $raw_title  = get_the_title();

            // Extrai artista e música
            $artist = '';
            $song   = $raw_title;
            if (strpos($raw_title, '/') !== false) {
                $parts = explode('/', $raw_title, 2);
                $artist = trim($parts[0]);
                $song   = isset($parts[1]) ? trim($parts[1]) : $raw_title;
            }

            // Limpa título da música
            if (function_exists('cmidi_clean_song_title')) {
                $song = cmidi_clean_song_title($song);
            }

            // Gênero musical
            $genres = wp_get_post_terms($product_id, 'genero', array('fields' => 'names'));
            $genre_label = (!empty($genres) && !is_wp_error($genres)) ? $genres[0] : '';

            // Preço
            $price_html = $product ? $product->get_price_html() : '';

            // Thumbnail / Imagem
            $image = wp_get_attachment_image_url($product ? $product->get_image_id() : 0, 'thumbnail');
            if (!$image && function_exists('wc_placeholder_img_src')) {
                $image = wc_placeholder_img_src('thumbnail');
            }

            $results[] = array(
                'id'         => $product_id,
                'title'      => $song,
                'artist'     => $artist,
                'genre'      => $genre_label,
                'url'        => get_permalink($product_id),
                'price_html' => $price_html,
                'image'      => $image,
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array(
        'results'    => $results,
        'total'      => $query->found_posts,
        'search_url' => home_url('/?s=' . urlencode($term) . '&post_type=product')
    ));
}


