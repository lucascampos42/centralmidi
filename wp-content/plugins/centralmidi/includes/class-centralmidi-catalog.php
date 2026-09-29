<?php
/**
 * Central MIDI Catalog: public shortcode with filters.
 *
 * Usage: [centralmidi_catalogo]
 */

defined('ABSPATH') || exit;

class CentralMidi_Catalog {

    public function __construct() {
        add_shortcode('centralmidi_catalogo', array($this, 'render'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        wp_register_style(
            'centralmidi-catalog',
            CENTRALMIDI_PLUGIN_URL . 'assets/css/catalog.css',
            array(),
            CENTRALMIDI_VERSION
        );
    }

    public function render($atts) {
        wp_enqueue_style('centralmidi-catalog');

        $atts = shortcode_atts(array(
            'por_pagina' => 12,
        ), $atts, 'centralmidi_catalogo');

        $filters = $this->get_filters();

        $product_ids = CentralMidi_DB::search_product_ids($filters);
        $product_ids = $product_ids ? $product_ids : array(-1);

        $paged = max(1, get_query_var('paged'));

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'post__in'       => $product_ids,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => absint($atts['por_pagina']),
            'paged'          => $paged,
        );

        $query = new WP_Query($args);

        ob_start();
        ?>
        <div class="centralmidi-catalogo" id="midis">
            <form class="centralmidi-filters" method="get">
                <?php $this->render_filter('artista', __('Artista', 'centralmidi'), $filters['artista'] ?? ''); ?>
                <?php $this->render_filter('genero', __('Gênero', 'centralmidi'), $filters['genero'] ?? ''); ?>
                <?php $this->render_filter('mes_lancamento', __('Mês de Lançamento', 'centralmidi'), $filters['mes_lancamento'] ?? '', 'mes'); ?>
                <?php $this->render_filter('classificacao', __('Classificação', 'centralmidi'), $filters['classificacao'] ?? '', 'classificacao'); ?>
                
                <div class="centralmidi-filter-buttons">
                    <button type="submit" class="centralmidi-btn centralmidi-btn-filter">
                        <i class="ri-filter-3-line"></i> <?php esc_html_e('Filtrar', 'centralmidi'); ?>
                    </button>
                    <?php if (!empty($filters)) : ?>
                        <a class="centralmidi-btn centralmidi-btn-clear" href="<?php echo esc_url(remove_query_arg(array('artista', 'genero', 'mes_lancamento', 'classificacao'))); ?>">
                            <i class="ri-close-circle-line"></i> <?php esc_html_e('Limpar Filtros', 'centralmidi'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="centralmidi-grid">
                <?php if ($query->have_posts()) : ?>
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <?php $this->render_card(get_the_ID()); ?>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="centralmidi-empty">
                        <i class="ri-music-line" style="font-size: 2.5rem; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p><?php esc_html_e('Nenhum MIDI encontrado com os filtros selecionados.', 'centralmidi'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($query->max_num_pages > 1) : ?>
                <div class="centralmidi-pagination">
                    <?php
                    echo paginate_links(array(
                        'total'   => $query->max_num_pages,
                        'current' => $paged,
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    private function get_filters() {
        $filters = array();
        foreach (array('artista', 'genero', 'mes_lancamento', 'classificacao') as $key) {
            if (isset($_GET[$key]) && '' !== $_GET[$key]) {
                $filters[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
            }
        }
        return $filters;
    }

    private function render_filter($key, $label, $selected, $type = 'text') {
        $options = array();
        if ('mes' === $type) {
            foreach (CentralMidi_DB::distinct('mes_lancamento') as $mes) {
                $options[$mes] = CentralMidi_DB::mes_nome($mes);
            }
        } elseif ('classificacao' === $type) {
            foreach (CentralMidi_DB::distinct('classificacao') as $class) {
                $options[$class] = '#' . $class . ' — ' . CentralMidi_DB::classificacao_label($class);
            }
        } else {
            foreach (CentralMidi_DB::distinct($key) as $value) {
                $options[$value] = $value;
            }
        }
        ?>
        <div class="centralmidi-filter">
            <label for="centralmidi-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
            <select id="centralmidi-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>">
                <option value=""><?php esc_html_e('Todos', 'centralmidi'); ?></option>
                <?php foreach ($options as $value => $text) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected((string) $selected, (string) $value); ?>><?php echo esc_html($text); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render a MIDI card via the theme component.
     *
     * Delegates to template-parts/card-midi.php so the same card is used
     * everywhere (catalog shortcode, home, artist page, etc.).
     */
    public static function render_card($product_id) {
        get_template_part('template-parts/card-midi', null, array('product_id' => (int) $product_id));
    }
}