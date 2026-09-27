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

// Filtro para usar imagem placeholder local
add_filter('woocommerce_placeholder_img_src', 'cmidi_local_placeholder');
function cmidi_local_placeholder($src) {
    $local_path = get_stylesheet_directory() . '/assets/images/placeholder.png';
    if (file_exists($local_path)) {
        return get_stylesheet_directory_uri() . '/assets/images/placeholder.png';
    }
    return $src;
}

// 1. Carrega a lógica de artistas personalizada
require get_stylesheet_directory() . '/inc/cmidi-artistas.php';

// Correção dinâmica de link no menu (Midis por Artista -> /artistas/)
add_filter('wp_get_nav_menu_items', 'cmidi_fix_menu_links', 10, 3);
function cmidi_fix_menu_links($items, $menu, $args) {
    foreach ($items as &$item) {
        if ($item->title == 'Midis por Artista') {
            $item->url = home_url('/artistas/');
        }
    }
    return $items;
}

// 2. Carrega CSS e scripts
function enqueue_child_theme_style_and_scripts() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

                // Carrega o style.css principal do Child Theme
    wp_enqueue_style( 'flatsome-child-style', get_stylesheet_uri() );

    // CSS customizado (O visual moderno) - Carrega globalmente para manter a consistência visual
    $css_ver = file_exists($theme_dir . '/assets/css/custom-style.css') ? filemtime($theme_dir . '/assets/css/custom-style.css') : '1.0';
    wp_enqueue_style('custom-modern-style', $theme_uri . '/assets/css/custom-style.css', array(), $css_ver);

    // Carregamento Condicional de Scripts e Lógica de Dados
    // Carrega apenas em páginas da loja, produtos, taxonomias, carrinho, checkout, home e resultados de busca
    if ( is_shop() || is_product() || is_product_category() || is_tax('product_cat') || is_tax('mes_de_lancamento') || is_tax('genero_musical') || is_tax('tipo') || is_cart() || is_checkout() || is_front_page() || is_search() || is_page('ferramentas-admin') ) {
        
        // Howler.js para áudio
        wp_enqueue_script('howlerjs', $theme_uri . '/assets/js/howler.min.js', array(), '2.2.3', true);
        
        // Script customizado (áudio)
        $js_ver = file_exists($theme_dir . '/assets/js/custom-script.js') ? filemtime($theme_dir . '/assets/js/custom-script.js') : '1.0';
        wp_enqueue_script('custom-script', $theme_uri . '/assets/js/custom-script.js', array('howlerjs', 'jquery'), $js_ver, true);

        // Cart manager (vanilla JS - alta performance, SEM jQuery)
        $cart_ver = file_exists($theme_dir . '/assets/js/cart-manager.js') ? filemtime($theme_dir . '/assets/js/cart-manager.js') : '1.0';
        wp_enqueue_script('cart-manager', $theme_uri . '/assets/js/cart-manager.js', array(), $cart_ver, true);

        // Localização de dados para o JS (Lógica do carrinho)
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
        ));
    }
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

// 3. Homepage Shortcode (Destaques do Mês) com Cache (Transient)
add_shortcode('get_featured', 'query_music_featured');
function query_music_featured() {
    $cached = get_transient('cmidi_featured_output_v18');
    if ($cached !== false) return $cached;

    setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
    date_default_timezone_set('America/Sao_Paulo');
    
    $month_abbr = array(
        '01' => 'jan', '02' => 'fev', '03' => 'mar',
        '04' => 'abr', '05' => 'mai', '06' => 'jun',
        '07' => 'jul', '08' => 'ago', '09' => 'set',
        '10' => 'out', '11' => 'nov', '12' => 'dez',
    );
    $months_full_names = array(
        '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
        '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
        '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
        '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
    );
    $meses = []; $meses_full = [];
    for ($i = -2; $i <= 0; $i++) {
        $timestamp = strtotime("$i month");
        $m = date_i18n('m', $timestamp);
        $y = date_i18n('Y', $timestamp);
        $meses[] = $month_abbr[$m] . '-' . $y;
        $meses_full[] = date_i18n('F Y', $timestamp);
    }
    $meses = array_reverse($meses);
    $meses_full = array_reverse($meses_full);

    // Get all available months for the nav with counts
    $all_months = array();
    if (class_exists('CentralMidi_DB')) {
        foreach (CentralMidi_DB::get_meses_disponiveis() as $row) {
            $all_months[] = (object) array(
                'slug' => CentralMidi_Frontend::mes_label($row['mes'], $row['ano']),
                'qty'  => $row['qtd'],
            );
        }
    }

    ob_start();

    // Month nav sidebar + featured content
    echo '<div class="fp-layout">';
    
    // Sidebar nav
    echo '<nav class="fp-nav"><h4 class="fp-nav-title">Meses</h4><ul class="fp-nav-list">';
    foreach ($all_months as $m) {
        // A rota de arquivo de mês não existe mais: o item virou texto puro.
        echo '<li><span class="fp-nav-label">' . esc_html(ucfirst($m->slug)) . '</span><span class="fp-nav-count">' . (int)$m->qty . '</span></li>';
    }
    echo '</ul><button class="fp-nav-toggle" onclick="this.previousElementSibling.classList.toggle(\'fp-nav-expanded\');this.textContent=this.textContent==\'Ver mais\'?\'Ver menos\':\'Ver mais\'">Ver mais</button></nav>';

    // Featured content (70%)
    echo '<div class="fp-main">';
    foreach ($meses as $idx => $slug) {
        list($f_mes, $f_ano) = array_pad(explode('-', $slug), 2, 0);
        $month_map = array('jan'=>'1','fev'=>'2','mar'=>'3','abr'=>'4','mai'=>'5','jun'=>'6',
                           'jul'=>'7','ago'=>'8','set'=>'9','out'=>'10','nov'=>'11','dez'=>'12');
        $f_mes = isset($month_map[strtolower($f_mes)]) ? (int) $month_map[strtolower($f_mes)] : 0;
        $f_ano = (int) $f_ano;

        $products = array();
        if ($f_mes && class_exists('CentralMidi_DB')) {
            foreach (CentralMidi_DB::get_midis_by_month($f_mes, $f_ano, 30) as $pid) {
                $p = wc_get_product($pid);
                if ($p) {
                    $products[] = $p;
                }
            }
        }
        if (empty($products)) continue;
        
        echo '<div class="fp-section">
                <div class="fp-section-header">
                    <h3 class="fp-month-title">Lançamentos de ' . $meses_full[$idx] . '</h3>
                    <button class="fp-play-all" onclick="playAllInSection(this)">
                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Ouvir Tudo
                    </button>
                </div>';
        $fp_ids = wp_list_pluck($products, 'id');
        $fp_meta = class_exists('CentralMidi_Frontend') ? CentralMidi_Frontend::get_many($fp_ids) : array();
        foreach ($products as $product) {
            $product_id = $product->get_id();
            $cmeta = isset($fp_meta[$product_id]) ? $fp_meta[$product_id] : array();
            $audio = !empty($cmeta['demo_url']) ? $cmeta['demo_url'] : '';
            $rlm_value = !empty($cmeta['rotulo']) ? $cmeta['rotulo'] : '';
            $player_id = 'player-demo-' . $product_id;
            $demo = $audio ? '<span class="fp-play-btn" onclick="playAudio(\'' . esc_url($audio) . '\', null, this);" title="Ouvir demonstração">
                <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><polygon points="6,4 14,10 6,16"/></svg>
                <span class="btn-text">PLAY</span>
            </span>' : '';
            $product_url = get_permalink($product_id);

            $genero_nome = !empty($cmeta['genero_nome']) ? $cmeta['genero_nome'] : '';
            $mes_label   = !empty($cmeta['mes_label']) ? $cmeta['mes_label'] : '';

            $parts = explode('/', $product->get_name(), 2);
            $artist_name = trim($parts[0]);
            $track_name = isset($parts[1]) ? trim($parts[1]) : '';

            echo '<div class="fp-card" data-audio="' . esc_url($audio) . '" data-product-id="' . $product_id . '">
                    <div class="fp-img">' . $product->get_image(array(60, 60), array('loading' => 'lazy')) . '</div>
                    <div class="fp-body">
                        <div class="fp-left">
                            <div class="fp-top">
                                <div class="fp-artist-row">
                                    <span class="fp-artist">' . esc_html($artist_name) . '</span>
                                    <div class="fp-meta">';
            if ($genero_nome) {
                echo '<span class="genero-tag">' . esc_html($genero_nome) . '</span>';
            }
            if ($rlm_value) {
                echo '<span class="rlm-tag">' . esc_html($rlm_value) . '</span>';
            }
            if ($mes_label) {
                echo '<span class="data-tag">' . esc_html($mes_label) . '</span>';
            }
            echo '                  </div>
                                </div>
                                <p class="fp-title"><a href="' . esc_url($product_url) . '">' . esc_html($track_name ?: $product->get_name()) . '</a></p>
                            </div>
                        </div>
                        <div class="fp-right">
                            <div class="fp-price-row">
                                <span class="fp-price">R$' . number_format((float)$product->get_regular_price(), 2, ',', '.') . '</span>
                                ' . $demo . '
                            </div>
                            <a href="?add-to-cart=' . $product_id . '" class="fp-btn add_to_cart_button ajax_add_to_cart" data-product_id="' . $product_id . '">Comprar</a>
                        </div>
                    </div>
                </div>';
        }
        echo '</div>';
    }
    echo '</div></div>';

    $output = ob_get_clean();
    set_transient('cmidi_featured_output_v18', $output, 600);
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

    // 2. RLM (Letra/Melodia)
    $rlm_cache = 'cmidi_rlm_' . $product_id;
    $rlm = wp_cache_get($rlm_cache, 'cmidi_fields');
    if ($rlm === false) {
        $rlm = get_field('rlm', $product_id);
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

    $audio = get_post_meta($product->get_id(), 'url_demo', true);
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

// 6. Corrige paginação de taxonomias (mes_de_lancamento, genero_musical)
add_action('init', 'cmidi_taxonomy_pagination_fix');
function cmidi_taxonomy_pagination_fix() {
    add_rewrite_rule('^mes_de_lancamento/[^/]+/page/([0-9]+)/?$', 'index.php?mes_de_lancamento=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^genero_musical/[^/]+/page/([0-9]+)/?$', 'index.php?genero_musical=$matches[1]&paged=$matches[2]', 'top');
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
        echo '<div class="meses-lancamentos" style="display: flex; flex-direction: column; gap: 8px; max-width: 300px;">';
        foreach($meses as $mes) {
            $url = get_term_link($mes[0], 'mes_de_lancamento');
            echo '<a href="' . esc_url($url) . '" class="mes-lancamento" style="display: flex; align-items: center; text-decoration: none; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right:10px; opacity:0.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span style="font-weight: 500;">' . esc_html($mes[1]) . '</span>
                    <span style="margin-left: auto; font-size: 0.85em; opacity: 0.6; background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 10px;">' . $mes[2] . '</span>
                  </a>';
        }
        echo '</div>';
        $output = ob_get_clean();
        
        set_transient('midis_por_mes_html_v2', $output, 1 * HOUR_IN_SECONDS); // Reduzido para 1h
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
      'hide_empty' => false // Mostra TODOS os gêneros, mesmo os vazios
    ));
        
    if (empty($terms) || is_wp_error($terms)) return '';

    ob_start();
    echo '<div class="generos-lista" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px; width: 100%;">';
    foreach($terms as $term) {
      $url = get_term_link($term->slug, 'genero_musical');        
      echo '<a href="' . esc_url($url) . '" class="genero-item" style="display: flex; align-items: center; text-decoration: none; padding: 12px; background: rgba(0,0,0,0.05); border-radius: 6px; transition: background 0.2s;">
              <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right:10px; opacity:0.5"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
              <span style="font-weight: 500; font-size: 0.95em;">' . esc_html($term->name) . '</span>
              <span style="margin-left: auto; font-size: 0.8em; opacity: 0.6; background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 10px;">' . $term->count . '</span>
            </a>';
    }
    echo '</div>';
    $output = ob_get_clean();
        
    set_transient('midis_por_genero_html_v2', $output, 1 * HOUR_IN_SECONDS);
    return $output;
  }
}

// 9. Player Global de Áudio no Rodapé
add_action('wp_footer', 'cmidi_global_audio_player');
function cmidi_global_audio_player() {
    // Carrega apenas nas páginas onde o player é necessário
    if ( is_shop() || is_product() || is_product_category() || is_tax() || is_front_page() || is_search() ) {
        ?>
        <div id="cmidi-global-player" class="cmidi-global-player" style="display: none;">
            <div class="player-content">
                <!-- Esquerda: Info -->
                <div class="player-left">
                    <div class="player-thumb">
                        <img id="player-thumb-img" src="" alt="" style="display: none;">
                        <div class="player-thumb-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                        </div>
                    </div>
                    <div class="player-info">
                        <span class="player-track-title">Nenhuma música tocando</span>
                        <span class="player-track-artist"></span>
                    </div>
                </div>

                <!-- Centro: Controles e Progresso -->
                <div class="player-center">
                    <div class="player-controls">
                        <button id="player-prev" class="player-btn" title="Anterior">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                        </button>
                        <button id="player-play-pause" class="player-btn play-btn" title="Play/Pause">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="icon-play"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                        <button id="player-next" class="player-btn" title="Próxima">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6zM16 6h2v12h-2z"/></svg>
                        </button>
                    </div>
                    <div class="player-progress-container">
                        <div class="player-current-time">0:00</div>
                        <div class="player-progress-bar">
                            <div class="player-progress-fill"></div>
                            <div class="player-progress-handle"></div>
                        </div>
                        <div class="player-total-time">0:00</div>
                    </div>
                </div>

                <!-- Direita: Ações -->
                <div class="player-right">
                    <div class="player-actions">
                        <button id="player-toggle-playlist" class="player-btn" title="Ver Playlist">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                        </button>
                        <a href="#" id="player-buy-link" class="player-buy-btn add_to_cart_button ajax_add_to_cart" data-product_id="">Comprar</a>
                    </div>
                </div>
            </div>
            <!-- Lista de Reprodução (Oculta por padrão) -->
            <div id="player-playlist-panel" class="player-playlist-panel">
                <div class="playlist-header">
                    <h4>Fila de Reprodução</h4>
                    <button id="close-playlist">&times;</button>
                </div>
                <div id="playlist-items" class="playlist-items">
                    <!-- Itens da playlist serão inseridos aqui via JS -->
                </div>
            </div>
        </div>
        <?php
    }
}

// === ADMIN TOOLS (sem tabela) ===

add_action('wp_ajax_pa_admin_stats', 'pa_admin_stats');
function pa_admin_stats() {
    check_ajax_referer('pa_admin_nonce', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Acesso negado');

    $stats = get_transient('pa_admin_stats_cache');
    if (false === $stats) {
        global $wpdb;
        $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'");
        $with_demo = (int)$wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} m ON p.ID=m.post_id
            WHERE p.post_type='product' AND p.post_status='publish'
              AND m.meta_key='url_demo' AND m.meta_value!=''
        ");
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

        if ($filtro_demo === 'has') {
            $join .= " JOIN {$wpdb->postmeta} md ON p.ID=md.post_id AND md.meta_key='url_demo' AND md.meta_value!=''";
        } elseif ($filtro_demo === 'none') {
            $join .= " LEFT JOIN {$wpdb->postmeta} md ON p.ID=md.post_id AND md.meta_key='url_demo' AND md.meta_value!=''";
            $where .= " AND md.post_id IS NULL";
        }

        if ($filtro_demo_search !== '') {
            $where .= $wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id=p.ID AND m.meta_key='url_demo' AND m.meta_value LIKE %s)", '%' . $wpdb->esc_like($filtro_demo_search) . '%');
        }
    }

    $total    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p $join WHERE $where");
    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title, rp.meta_value as regular_price, ti.meta_value as thumb_id
         FROM {$wpdb->posts} p
         $join
         LEFT JOIN {$wpdb->postmeta} rp ON p.ID=rp.post_id AND rp.meta_key='_regular_price'
         LEFT JOIN {$wpdb->postmeta} ti ON p.ID=ti.post_id AND ti.meta_key='_thumbnail_id'
         WHERE $where ORDER BY p.ID DESC LIMIT %d OFFSET %d",
        $limit, $offset
    ));

    $results = array();
    if (!empty($products)) {
        $ids      = wp_list_pluck($products, 'ID');
        $ids_flat = implode(',', array_map('intval', $ids));

        $term_rows = $wpdb->get_results(
            "SELECT tr.object_id, t.term_id, t.name, tt.taxonomy
             FROM {$wpdb->term_relationships} tr
             JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE tr.object_id IN ($ids_flat)
             AND tt.taxonomy IN ('genero_musical','mes_de_lancamento','tipo','product_cat')"
        );

        $terms_by_post = array();
        foreach ($term_rows as $row) {
            $pid = $row->object_id;
            $tax = $row->taxonomy;
            if (!isset($terms_by_post[$pid])) $terms_by_post[$pid] = array();
            if (!isset($terms_by_post[$pid][$tax])) $terms_by_post[$pid][$tax] = array();
            $terms_by_post[$pid][$tax][] = $row;
        }

        $demo_by_post = array();
        $rlm_by_post = array();
        $meta_rows = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($ids_flat) AND meta_key IN ('url_demo', 'rlm')");
        foreach ($meta_rows as $row) {
            if ($row->meta_key === 'url_demo') $demo_by_post[$row->post_id] = $row->meta_value;
            if ($row->meta_key === 'rlm')      $rlm_by_post[$row->post_id]  = $row->meta_value;
        }

        foreach ($products as $p) {
            $pid = $p->ID;
            $pt = isset($terms_by_post[$pid]) ? $terms_by_post[$pid] : array();

            $artistas   = array();
            $generos    = array();
            $tipos      = array();
            $meses      = array();
            $genero_ids = array();
            $mes_ids    = array();
            $tipo_ids   = array();

            if (isset($pt['product_cat'])) {
                foreach ($pt['product_cat'] as $t) $artistas[] = $t->name;
            }
            if (isset($pt['genero_musical'])) {
                foreach ($pt['genero_musical'] as $t) { $generos[] = $t->name; $genero_ids[] = (int)$t->term_id; }
            }
            if (isset($pt['mes_de_lancamento'])) {
                foreach ($pt['mes_de_lancamento'] as $t) { $meses[] = $t->name; $mes_ids[] = (int)$t->term_id; }
            }
            if (isset($pt['tipo'])) {
                foreach ($pt['tipo'] as $t) { $tipos[] = $t->name; $tipo_ids[] = (int)$t->term_id; }
            }

            $url_demo = isset($demo_by_post[$pid]) ? $demo_by_post[$pid] : '';
            $rlm      = isset($rlm_by_post[$pid]) ? $rlm_by_post[$pid] : '';
            $thumb    = $p->thumb_id ? wp_get_attachment_image_url($p->thumb_id, array(40, 40)) : '';

            $results[] = array(
                'id'       => $pid,
                'title'    => $p->post_title,
                'artista'  => $artistas,
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
            update_post_meta($product_id, 'url_demo', esc_url_raw($value));
            break;
        case 'rlm':
            update_field('rlm', sanitize_text_field($value), $product_id);
            break;
        case 'genero_musical':
            $terms = array_map('intval', (array)$value);
            $terms = array_filter($terms);
            wp_set_post_terms($product_id, $terms, 'genero_musical');
            break;
        case 'mes_de_lancamento':
            $terms = array_map('intval', (array)$value);
            $terms = array_filter($terms);
            wp_set_post_terms($product_id, $terms, 'mes_de_lancamento');
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

    // 2. Produtos com mesma URL de demo
    $urls = $wpdb->get_results(
        "SELECT m.post_id, p.post_title, m.meta_value
         FROM {$wpdb->postmeta} m
         JOIN {$wpdb->posts} p ON m.post_id = p.ID
         JOIN (
             SELECT m2.meta_value
             FROM {$wpdb->postmeta} m2
             JOIN {$wpdb->posts} p2 ON m2.post_id = p2.ID
             WHERE m2.meta_key='url_demo' AND m2.meta_value != ''
               AND p2.post_type='product' AND p2.post_status='publish'
             GROUP BY m2.meta_value
             HAVING COUNT(*) > 1
         ) dup ON m.meta_value = dup.meta_value
         WHERE m.meta_key='url_demo'
           AND p.post_type='product' AND p.post_status='publish'
         ORDER BY m.meta_value, p.ID"
    );
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



