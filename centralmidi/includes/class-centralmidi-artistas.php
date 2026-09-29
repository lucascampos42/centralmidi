<?php
/**
 * Central MIDI - Catálogo e Navegação Unificada de Artistas (AJAX)
 *
 * Desacoplado do tema Flatsome. Gerencia URLs /artistas/, /midis/ e busca dinâmica.
 */

defined('ABSPATH') || exit;

class CentralMidi_Artistas {

    public function __construct() {
        // Rewrite Rules e Query Vars
        add_action('init', array($this, 'register_rewrite_rules'));
        add_filter('query_vars', array($this, 'register_query_vars'));

        // Redirecionamento e Renderização de Template
        add_action('template_redirect', array($this, 'handle_template_redirect'), 4);

        // Shortcode (retrocompatibilidade)
        add_shortcode('cmidi_artistas', array($this, 'render_shortcode'));

        // Endpoints AJAX
        add_action('wp_ajax_cmidi_get_artistas', array($this, 'ajax_get_artistas'));
        add_action('wp_ajax_nopriv_cmidi_get_artistas', array($this, 'ajax_get_artistas'));

        // Assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function register_rewrite_rules() {
        add_rewrite_rule('^artistas/([^/]+)/?$', 'index.php?cmidi_letra=$matches[1]', 'top');
        add_rewrite_rule('^artistas/?$', 'index.php?cmidi_artistas=1', 'top');
        add_rewrite_rule('^midis/([^/]+)/?$', 'index.php?cmidi_letra=$matches[1]', 'top');
        add_rewrite_rule('^midis/?$', 'index.php?cmidi_artistas=1', 'top');
    }

    public function register_query_vars($vars) {
        $vars[] = 'cmidi_letra';
        $vars[] = 'cmidi_artistas';
        return $vars;
    }

    public function enqueue_assets() {
        wp_register_style(
            'centralmidi-artistas',
            CENTRALMIDI_PLUGIN_URL . 'assets/css/artistas.css',
            array(),
            CENTRALMIDI_VERSION . '.' . time()
        );

        wp_register_script(
            'centralmidi-artistas',
            CENTRALMIDI_PLUGIN_URL . 'assets/js/artistas.js',
            array('jquery'),
            CENTRALMIDI_VERSION . '.' . time(),
            true
        );

        wp_localize_script('centralmidi-artistas', 'cmidiArtistasData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cmidi_artistas_nonce'),
            'home_url' => home_url('/'),
        ));
    }

    public function is_artistas_page() {
        $letra = get_query_var('cmidi_letra');
        $artistas = get_query_var('cmidi_artistas');

        if (!empty($letra) || !empty($artistas)) {
            return true;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI'])[0] : '';
        $path = trim(parse_url($uri, PHP_URL_PATH), '/');

        // Bate com 'artistas', 'midis', 'artistas/X', 'midis/X'
        if ($path === 'artistas' || $path === 'midis') {
            return true;
        }
        $parts = explode('/', $path);
        if (count($parts) === 2 && in_array($parts[0], array('artistas', 'midis'))) {
            return true;
        }

        return false;
    }

    public function handle_template_redirect() {
        if ($this->is_artistas_page()) {
            // Remove o cabeçalho antigo do Flatsome (breadcrumbs, filtros da loja, total e ordenação)
            remove_action('flatsome_after_header', 'flatsome_category_header');

            wp_enqueue_style('centralmidi-artistas');
            wp_enqueue_script('centralmidi-artistas');

            get_header();
            echo $this->render_page();
            get_footer();
            exit;
        }
    }

    public function render_shortcode($atts) {
        wp_enqueue_style('centralmidi-artistas');
        wp_enqueue_script('centralmidi-artistas');
        return $this->render_page();
    }

    /**
     * Consulta as letras disponíveis no catálogo com contagem
     */
    public function get_letras_disponiveis() {
        global $wpdb;
        $cache_key = 'cmidi_letras_com_artistas_v3';
        $letras_ativas = get_transient($cache_key);

        if (false === $letras_ativas) {
            $results = $wpdb->get_col(
                "SELECT DISTINCT UPPER(LEFT(t.name, 1)) as first_char
                 FROM {$wpdb->terms} t
                 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                 WHERE tt.taxonomy = 'product_cat'
                 AND tt.count > 0"
            );

            $letras_ativas = array();
            $tem_outros = false;

            foreach ($results as $c) {
                if (preg_match('/^[A-Z]/', $c)) {
                    $letras_ativas[$c] = true;
                } else {
                    $tem_outros = true;
                }
            }

            if ($tem_outros) {
                $letras_ativas['#'] = true;
            }

            set_transient($cache_key, $letras_ativas, 12 * HOUR_IN_SECONDS);
        }

        return $letras_ativas;
    }

    /**
     * Busca artistas por letra ou termo de pesquisa
     */
    public function query_artistas($letra = 'A', $search = '') {
        global $wpdb;
        $letra = strtoupper(trim($letra));
        $search = trim($search);

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT t.term_id, t.name, t.slug, tt.count
                     FROM {$wpdb->terms} t
                     INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                     WHERE tt.taxonomy = 'product_cat'
                     AND tt.count > 0
                     AND t.name LIKE %s
                     ORDER BY t.name ASC
                     LIMIT 150",
                    $like
                )
            );
        }

        if ($letra === '#') {
            return $wpdb->get_results(
                "SELECT t.term_id, t.name, t.slug, tt.count
                 FROM {$wpdb->terms} t
                 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                 WHERE tt.taxonomy = 'product_cat'
                 AND tt.count > 0
                 AND t.name REGEXP '^[^A-Za-z]'
                 ORDER BY t.name ASC"
            );
        }

        if (empty($letra)) {
            $letra = 'A';
        }

        $like = $wpdb->esc_like($letra) . '%';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.term_id, t.name, t.slug, tt.count
                 FROM {$wpdb->terms} t
                 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                 WHERE tt.taxonomy = 'product_cat'
                 AND tt.count > 0
                 AND t.name LIKE %s
                 ORDER BY t.name ASC",
                $like
            )
        );
    }

    /**
     * Endpoint AJAX para retornar os artistas
     */
    public function ajax_get_artistas() {
        check_ajax_referer('cmidi_artistas_nonce', 'nonce');

        $letra = isset($_POST['letra']) ? sanitize_text_field($_POST['letra']) : 'A';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        $artistas = $this->query_artistas($letra, $search);
        $total = count($artistas);

        ob_start();
        if ($total > 0) {
            foreach ($artistas as $artista) {
                $this->render_card_artista($artista);
            }
        } else {
            echo '<div class="cmidi-artistas-empty">
                <i class="ri-user-unfollow-line"></i>
                <p>Nenhum artista encontrado para esta seleção.</p>
            </div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(array(
            'total'  => $total,
            'letra'  => $letra,
            'search' => $search,
            'html'   => $html,
        ));
    }

    /**
     * Renderiza o Card individual de um Artista
     */
    public function render_card_artista($artista) {
        $term_link = get_term_link((int) $artista->term_id, 'product_cat');
        if (is_wp_error($term_link)) {
            $term_link = home_url('/artistas/' . strtolower($artista->slug) . '/');
        }

        $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($artista->name) . '&background=00d284&color=0b1120&size=180&bold=true';
        ?>
        <div class="cmidi-artista-card">
            <a href="<?php echo esc_url($term_link); ?>" class="cmidi-artista-link" title="<?php echo esc_attr($artista->name); ?>">
                <div class="cmidi-artista-avatar">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($artista->name); ?>" loading="lazy" />
                    <span class="cmidi-artista-badge"><?php echo (int) $artista->count; ?> MIDIs</span>
                </div>
                <div class="cmidi-artista-details">
                    <h3 class="cmidi-artista-name"><?php echo esc_html($artista->name); ?></h3>
                    <span class="cmidi-artista-action"><i class="ri-play-circle-line"></i> Ver Músicas</span>
                </div>
            </a>
        </div>
        <?php
    }

    /**
     * Renderiza a página completa (SSR inicial + container para AJAX)
     */
    public function render_page() {
        $letra_req = get_query_var('cmidi_letra');
        if (empty($letra_req)) {
            $uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI'])[0] : '';
            $parts = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
            if (count($parts) === 2 && in_array($parts[0], array('artistas', 'midis'))) {
                $letra_req = $parts[1];
            }
        }

        if (empty($letra_req)) {
            $letra_ativa = 'A';
        } else {
            $letra_ativa = strtoupper($letra_req === 'outros' ? '#' : $letra_req);
        }

        $letras_disponiveis = $this->get_letras_disponiveis();
        $artistas_iniciais = $this->query_artistas($letra_ativa);
        $total_iniciais = count($artistas_iniciais);

        $letras_alfabeto = array_merge(range('A', 'Z'), array('#'));

        ob_start();
        ?>
        <div class="cmidi-artistas-page-wrapper">
            <div class="cmidi-artistas-container">

                <!-- Header e Introdução -->
                <div class="cmidi-artistas-header">
                    <div class="cmidi-artistas-title-block">
                        <h1 class="cmidi-artistas-title">
                            <i class="ri-user-star-line"></i> Catálogo de Artistas
                        </h1>
                        <p class="cmidi-artistas-subtitle">
                            Navegue de A a Z por todos os intérpretes e bandas do acervo Central MIDI.
                        </p>
                    </div>

                    <!-- Busca Instantânea de Artistas -->
                    <div class="cmidi-artistas-search-box">
                        <i class="ri-search-line cmidi-search-icon"></i>
                        <input type="text" id="cmidi-artist-search-input" placeholder="Buscar artista diretamente por nome..." autocomplete="off">
                        <button type="button" id="cmidi-clear-search-btn" class="cmidi-clear-btn" style="display: none;">
                            <i class="ri-close-circle-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- Aviso Oficial de Classificação #RLM (Preservado conforme solicitado) -->
                <div class="cmidi-az-notice rlm-notice">
                    <strong>Classificação #RLM</strong><br>
                    #M = Midis somente com Melodia<br>
                    #L = Midis somente com Letra sincronizada<br>
                    #RLM = Midis com Melodia e Letra sincronizada<br>
                    Caso não haja essa classificação, considere portanto que o midi não tem melodia nem Letra!
                </div>

                <!-- Barra de Navegação Alfabética A-Z Instantânea -->
                <nav class="cmidi-alphabet-nav" aria-label="Navegação por letras">
                    <div class="cmidi-alphabet-buttons" id="cmidi-alphabet-bar">
                        <?php foreach ($letras_alfabeto as $letra) :
                            $tem_artistas = isset($letras_disponiveis[$letra]);
                            $is_active = ($letra === $letra_ativa);
                            $url_letra = home_url('/artistas/' . ($letra === '#' ? 'outros' : strtolower($letra)) . '/');
                            ?>
                            <button type="button"
                                    class="cmidi-letter-btn <?php echo $is_active ? 'is-active' : ''; ?> <?php echo !$tem_artistas ? 'is-disabled' : ''; ?>"
                                    data-letter="<?php echo esc_attr($letra); ?>"
                                    data-url="<?php echo esc_url($url_letra); ?>"
                                    <?php echo !$tem_artistas ? 'disabled' : ''; ?>>
                                <?php echo esc_html($letra); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </nav>

                <!-- Cabeçalho do Grupo de Resultados -->
                <div class="cmidi-results-status-bar">
                    <div class="cmidi-status-left">
                        <h2 class="cmidi-group-heading" id="cmidi-active-heading">
                            Artistas com a letra <span class="cmidi-accent" id="cmidi-active-letter-label"><?php echo esc_html($letra_ativa); ?></span>
                        </h2>
                    </div>
                    <div class="cmidi-status-right">
                        <span class="cmidi-count-pill" id="cmidi-active-count">
                            <?php echo esc_html($total_iniciais); ?> artistas encontrados
                        </span>
                    </div>
                </div>

                <!-- Container do Grid de Artistas (com suporte a AJAX) -->
                <div class="cmidi-grid-container">
                    <div class="cmidi-loading-overlay" id="cmidi-loading-indicator" style="display: none;">
                        <div class="cmidi-spinner"></div>
                        <span>Carregando artistas...</span>
                    </div>

                    <div class="cmidi-artistas-cards-grid" id="cmidi-artistas-grid">
                        <?php
                        if ($total_iniciais > 0) {
                            foreach ($artistas_iniciais as $artista) {
                                $this->render_card_artista($artista);
                            }
                        } else {
                            echo '<div class="cmidi-artistas-empty">
                                <i class="ri-user-unfollow-line"></i>
                                <p>Nenhum artista cadastrado com esta inicial.</p>
                            </div>';
                        }
                        ?>
                    </div>
                </div>

            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
