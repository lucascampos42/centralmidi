<?php
/**
 * Lógica de Artistas MIDI - Migrada do Tema Pai (Versão Estável)
 */

add_shortcode('cmidi_artistas', 'cmidi_artistas_shortcode');

function cmidi_artistas_shortcode($atts) {
    if ( ! function_exists('get_terms') ) return '';

    $letra_selecionada = isset($_GET['letra']) ? sanitize_text_field($_GET['letra']) : '';
    if (empty($letra_selecionada)) {
        $qv = get_query_var('cmidi_letra');
        if (!empty($qv)) $letra_selecionada = $qv === 'outros' ? '#' : $qv;
    }
    $letra_filtro = !empty($letra_selecionada) ? strtoupper($letra_selecionada) : '';
    
    $cache_key = empty($letra_filtro) ? 'cmidi_artistas_all_v2' : 'cmidi_artistas_v2_' . $letra_filtro;
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;

    $current_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
    $base_slug = (strpos($current_uri, '/midis') !== false) ? 'midis' : 'artistas';
    $base_url = home_url('/' . $base_slug . '/');
    
    $letras = range('A', 'Z');
    $letras[] = '#';

    // Quais letras têm artistas? (consulta leve, só as iniciais)
    global $wpdb;
    $letras_com_artistas = $wpdb->get_col(
        "SELECT DISTINCT UPPER(LEFT(t.name, 1)) as first_letter
         FROM {$wpdb->terms} t
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
         INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
         INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
         WHERE tt.taxonomy = 'product_cat'
         AND p.post_type = 'product'
         AND p.post_status = 'publish'
         GROUP BY first_letter
         ORDER BY first_letter ASC"
    );
    // Normaliza e agrupa letras
    $letras_com_artistas_normalizadas = array();
    foreach ($letras_com_artistas as $l) {
        $nl = normaliza_caractere_midis_child(strtoupper($l));
        $letras_com_artistas_normalizadas[$nl] = true;
    }
    // Mapeia # para letras não-A-Z
    $tem_outros = false;
    foreach ($letras_com_artistas as $l) {
        $nl = normaliza_caractere_midis_child(strtoupper($l));
        if (!preg_match('/^[A-Z]/', $nl)) {
            $tem_outros = true;
            break;
        }
    }

    $container_state = !empty($letra_filtro) ? 'is-filtered-index' : 'is-main-index';
    $output = '<div class="cmidi-artistas-container ' . $container_state . '">';
    $output .= '<div class="cmidi-az-notice rlm-notice">';
    $output .= '<strong>Classificação #RLM</strong><br>';
    $output .= '#M = Midis somente com Melodia<br>';
    $output .= '#L = Midis somente com Letra sincronizada<br>';
    $output .= '#RLM = Midis com Melodia e Letra sincronizada<br>';
    $output .= 'Caso não haja essa classificação, considere portanto que o midi não tem melodia nem Letra!';
    $output .= '</div>';
    $output .= '<div class="cmidi-az-navigation">';
    
    foreach ($letras as $letra) {
        $tem_artistas = $letra === '#' ? $tem_outros : isset($letras_com_artistas_normalizadas[$letra]);
        $link = $base_url . ($letra === '#' ? 'outros' : strtoupper($letra)) . '/';
        $output .= '<a href="' . esc_url($link) . '" class="cmidi-az-btn ' . ($letra_filtro === $letra ? 'active' : '') . ' ' . (!$tem_artistas ? 'disabled' : '') . '">' . esc_html($letra) . '</a>';
    }
    $output .= '</div>';

    if (empty($letra_filtro)) {
        $output .= '<div class="cmidi-instrucao-artista" style="text-align:center; padding: 60px 20px; background: #fff; border-radius: 20px; border: 1px dashed #ddd; margin-top: 20px;">
            <svg viewBox="0 0 24 24" width="48" height="48" stroke="#ccc" stroke-width="1.5" fill="none" style="margin-bottom: 20px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            <h3 style="color: #666; font-weight: 600;">Selecione uma letra acima para listar os artistas</h3>
            <p style="color: #999;">Temos milhares de MIDIs organizados por ordem alfabética para facilitar sua busca.</p>
        </div>';
    } else {
        // Só carrega artistas quando uma letra é selecionada
        if ($letra_filtro === '#') {
            $artistas = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC',
            ));
            $artistas_filtrados = array();
            foreach ($artistas as $a) {
                $pl = normaliza_caractere_midis_child(strtoupper(substr($a->name, 0, 1)));
                if (!preg_match('/^[A-Z]/', $pl)) {
                    $artistas_filtrados[] = $a;
                }
            }
        } else {
            $artistas_filtrados = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'name__like' => $letra_filtro,
                'orderby' => 'name',
                'order' => 'ASC',
            ));
            // name__like trás resultados que contêm a letra em qualquer posição.
            // Filtramos para pegar só quem começa com a letra exata (após normalização)
            $artistas_filtrados = array_filter($artistas_filtrados, function($a) use ($letra_filtro) {
                $pl = normaliza_caractere_midis_child(strtoupper(substr($a->name, 0, 1)));
                return $pl === $letra_filtro;
            });
        }

        $output .= '<div class="cmidi-artistas-grid">';
        $output .= '<div class="cmidi-letra-grupo">';
        $output .= '<h3 class="cmidi-letra-titulo">' . esc_html($letra_filtro) . '</h3>';
        $output .= '<div class="cmidi-artistas-lista">';
        foreach ($artistas_filtrados as $artista) {
            $thumbnail_id = get_term_meta($artista->term_id, 'thumbnail_id', true);
            $image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : wc_placeholder_img_src();
            $term_link = get_term_link($artista);
            $output .= '<div class="cmidi-artista-card">
                <a href="' . esc_url($term_link) . '" class="cmidi-artista-link">
                    <div class="cmidi-artista-imagem"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($artista->name) . '" loading="lazy"></div>
                    <div class="cmidi-artista-info">
                        <h4 class="cmidi-artista-nome">' . esc_html($artista->name) . '</h4>
                        <span class="cmidi-artista-count">' . $artista->count . ' MIDIs</span>
                    </div>
                </a>
            </div>';
        }
        $output .= '</div></div>';
        $output .= '</div>';
    }

    $output .= '</div>';
    
    set_transient($cache_key, $output, 3600);
    
    return $output;
}

function normaliza_caractere_midis_child($char) {
    if (empty($char)) return $char;
    $map = array('Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','É'=>'E','È'=>'E','Ê'=>'E','Í'=>'I','Ì'=>'I','Î'=>'I','Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ú'=>'U','Ù'=>'U','Û'=>'U','Ç'=>'C','Ñ'=>'N');
    return isset($map[$char]) ? $map[$char] : $char;
}

// 2. Regras de Rewrite
add_action('init', 'cmidi_child_rewrite_rules_stable');
function cmidi_child_rewrite_rules_stable() {
    add_rewrite_rule('^artistas/([^/]+)/?$', 'index.php?cmidi_letra=$matches[1]', 'top');
    add_rewrite_rule('^artistas/?$', 'index.php?cmidi_artistas=1', 'top');
    add_rewrite_rule('^midis/([^/]+)/?$', 'index.php?cmidi_letra=$matches[1]', 'top');
    add_rewrite_rule('^midis/?$', 'index.php?cmidi_artistas=1', 'top');
}

add_filter('query_vars', function($vars) {
    $vars[] = 'cmidi_letra';
    $vars[] = 'cmidi_artistas';
    return $vars;
});

add_action('template_redirect', 'cmidi_child_template_redirect_stable', 5);
function cmidi_child_template_redirect_stable() {
    $letra = get_query_var('cmidi_letra');
    $artistas = get_query_var('cmidi_artistas');

    $request_uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI'])[0] : '';
    $request_path = rtrim(parse_url($request_uri, PHP_URL_PATH), '/');

    // Também força se a URL bater com /midis/ ou /artistas/ (inclusive quando WooCommerce rouba a query)
    if (in_array($request_path, ['/midis', '/artistas'])) {
        if (!isset($_GET['letra'])) {
            $artistas = 1;
        }
    }

    if ($letra || $artistas) {
        $letra_filtro = '';
        if ($letra) {
            $letra_filtro = strtoupper($letra === 'outros' ? '#' : $letra);
        }

        $template = locate_template('page-artistas.php');
        if ($template) {
            include($template);
            exit;
        }
    }
}
