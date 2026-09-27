<?php
/**
 * Central MIDI Migration: one-off, idempotent importer that copies an existing
 * WooCommerce catalog (legacy taxonomies + ACF fields) into the plugin tables.
 *
 * Source (legacy, read-only):
 *   - artistas : child terms of `product_cat` (roots are the A-Z letters)
 *   - foto     : `thumbnail_id` termmeta on those artists
 *   - genero   : `genero_musical` terms
 *   - mes/ano  : `mes_de_lancamento` terms (slug `set-2025`, name `SET 2025`)
 *   - rlm      : ACF field with free text (`Letra | Melodia`, `Letra`, `Melodia`)
 *   - demo     : `url_demo` postmeta (path under /demos/)
 *
 * Destination (additive only, nothing legacy is ever deleted):
 *   - wp_centralmidi_artistas / wp_centralmidi_generos / wp_centralmidi_midis
 *   - the nine `_centralmidi_*` postmeta keys
 *
 * The run is driven from the browser in small batches so it survives PHP time
 * limits, and every step is idempotent: interrupting and re-running is safe.
 */

defined('ABSPATH') || exit;

class CentralMidi_Migration {

    /** Products handled per AJAX request. */
    const BATCH_SIZE = 200;

    /** term_id => referencia id, built once by the prepare step. */
    const MAPS_OPTION = 'centralmidi_migration_maps';

    /** Progress cursor, so a run can be interrupted and resumed. */
    const STATE_OPTION = 'centralmidi_migration_state';

    /** Aggregated conflict counters + a capped sample for the report. */
    const CONFLICT_OPTION = 'centralmidi_migration_conflicts';

    /** How many example rows to keep per conflict type. */
    const CONFLICT_SAMPLE = 50;

    /** The nine denormalized postmeta keys this importer owns. */
    const META_KEYS = array(
        '_centralmidi_artista',
        '_centralmidi_artista_id',
        '_centralmidi_genero',
        '_centralmidi_genero_id',
        '_centralmidi_mes_lancamento',
        '_centralmidi_ano_lancamento',
        '_centralmidi_classificacao',
        '_centralmidi_publicado',
        '_centralmidi_demo_audio',
    );

    /** Portuguese month prefixes used by the legacy term slugs/names. */
    const MESES = array(
        'jan' => 1, 'fev' => 2, 'mar' => 3, 'abr' => 4, 'mai' => 5, 'jun' => 6,
        'jul' => 7, 'ago' => 8, 'set' => 9, 'out' => 10, 'nov' => 11, 'dez' => 12,
    );

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
        add_action('wp_ajax_centralmidi_migration_analyze', array($this, 'handle_analyze'));
        add_action('wp_ajax_centralmidi_migration_prepare', array($this, 'handle_prepare'));
        add_action('wp_ajax_centralmidi_migration_batch', array($this, 'handle_batch'));
        add_action('wp_ajax_centralmidi_migration_reset', array($this, 'handle_reset'));
    }

    /* ------------------------------------------------------------------
     * Admin UI
     * ---------------------------------------------------------------- */

    public function register_menu() {
        add_submenu_page(
            'centralmidi',
            __('Migração', 'centralmidi'),
            __('Migração', 'centralmidi'),
            'manage_options',
            'centralmidi-migracao',
            array($this, 'render_page')
        );
    }

    public function enqueue($hook) {
        // Match by `page` rather than the hook suffix, like enqueue_admin_assets()
        // does, so the assets load regardless of the parent menu slug.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ('centralmidi-migracao' !== $page) {
            return;
        }

        wp_enqueue_style(
            'centralmidi-migration',
            CENTRALMIDI_PLUGIN_URL . 'assets/css/admin-migration.css',
            array(),
            CENTRALMIDI_VERSION
        );
        wp_enqueue_script(
            'centralmidi-migration',
            CENTRALMIDI_PLUGIN_URL . 'assets/js/migration.js',
            array('jquery'),
            CENTRALMIDI_VERSION,
            true
        );
        wp_localize_script('centralmidi-migration', 'CentralMidiMigration', array(
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('centralmidi_migration'),
            'batchSize'  => self::BATCH_SIZE,
            'conflitos'  => $this->conflict_log(),
            'i18n'       => array(
                'analisando'  => __('Analisando…', 'centralmidi'),
                'preparando'  => __('Criando artistas e gêneros…', 'centralmidi'),
                'migrando'    => __('Migrando…', 'centralmidi'),
                'concluido'   => __('Migração concluída.', 'centralmidi'),
                'erro'        => __('Erro:', 'centralmidi'),
                'confirmExec' => __('Isso vai gravar nas tabelas do Central MIDI. Nenhum dado legado será apagado. Continuar?', 'centralmidi'),
                'confirmReset'=> __('Apagar o progresso e o relatório de conflitos?', 'centralmidi'),
            ),
        ));
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Central MIDI — Migração', 'centralmidi'); ?></h1>
            <p>
                <?php esc_html_e('Importa os dados do catálogo atual (taxonomias legadas e campos ACF) para as tabelas do plugin Central MIDI. A migração é aditiva: nada é apagado do WooCommerce, das taxonomias antigas ou dos campos ACF.', 'centralmidi'); ?>
            </p>

            <div class="notice notice-warning inline">
                <p>
                    <strong><?php esc_html_e('Faça um backup completo do banco antes de executar.', 'centralmidi'); ?></strong>
                    <?php esc_html_e(' Acompanhe o progresso abaixo; pode interromper e retomar a qualquer momento — a operação é idempotente.', 'centralmidi'); ?>
                </p>
            </div>

            <h2><?php esc_html_e('1. Análise (somente leitura)', 'centralmidi'); ?></h2>
            <p>
                <button type="button" class="button button-secondary" id="cm-mig-analyze">
                    <?php esc_html_e('Analisar catálogo atual', 'centralmidi'); ?>
                </button>
                <span id="cm-mig-analyze-status" class="cm-mig-status"></span>
            </p>
            <div id="cm-mig-analysis" class="cm-mig-panel" hidden></div>

            <h2><?php esc_html_e('2. Executar migração', 'centralmidi'); ?></h2>
            <p>
                <button type="button" class="button button-primary" id="cm-mig-run" disabled>
                    <?php esc_html_e('Executar migração', 'centralmidi'); ?>
                </button>
                <button type="button" class="button" id="cm-mig-reset" disabled>
                    <?php esc_html_e('Zerar progresso', 'centralmidi'); ?>
                </button>
                <span id="cm-mig-run-status" class="cm-mig-status"></span>
            </p>
            <div class="cm-mig-progress" hidden>
                <progress id="cm-mig-bar" value="0" max="100"></progress>
                <span id="cm-mig-count">0 / 0</span>
            </div>
            <div id="cm-mig-report" class="cm-mig-panel" hidden></div>

            <div id="cm-mig-conflitos" class="cm-mig-panel" hidden></div>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------
     * AJAX: analysis
     * ---------------------------------------------------------------- */

    public function handle_analyze() {
        $this->guard();
        global $wpdb;

        $produtos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'"
        );

        $artistas = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT t.term_id) FROM {$wpdb->terms} t
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
             WHERE tt.taxonomy='product_cat' AND tt.parent <> 0"
        );
        $artistas_foto = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT t.term_id) FROM {$wpdb->terms} t
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
             JOIN {$wpdb->termmeta} tm ON tm.term_id = t.term_id AND tm.meta_key='thumbnail_id' AND NULLIF(tm.meta_value, '0') IS NOT NULL AND tm.meta_value <> ''
             WHERE tt.taxonomy='product_cat' AND tt.parent <> 0"
        );
        $generos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy='genero_musical'"
        );

        // Distribution of the legacy free-text `rlm` field, and how it maps.
        $rlm_rows = $wpdb->get_results(
            "SELECT COALESCE(NULLIF(TRIM(meta_value), ''), ' ') AS valor, COUNT(*) AS n
             FROM {$wpdb->postmeta} WHERE meta_key='rlm'
             GROUP BY valor ORDER BY n DESC"
        );
        $rlm_total = 0;
        $rlm_map   = array();
        $rlm_outras = 0;
        foreach ($rlm_rows as $r) {
            list($code, $ok) = self::map_classificacao(' ' === $r->valor ? '' : $r->valor);
            $rlm_total += (int) $r->n;
            $label = ( '' === $r->valor ) ? '(vazio)' : $r->valor;
            $rlm_map[] = array(
                'valor'  => $label,
                'n'      => (int) $r->n,
                'vira'   => '' === $code ? '—' : $code,
                'ok'     => $ok,
            );
            if (!$ok) {
                $rlm_outras += (int) $r->n;
            }
        }

        $com_rlm    = $rlm_total;
        $sem_rlm    = max(0, $produtos - $rlm_total);
        $com_demo   = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key='url_demo' AND meta_value <> ''"
        );
        $demo_duplo = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='url_demo' AND meta_value <> '' GROUP BY post_id HAVING COUNT(*) > 1) x"
        );
        $multi_artista = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM (SELECT tr.object_id FROM {$wpdb->term_relationships} tr
              JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
              WHERE tt.taxonomy='product_cat' AND tt.parent <> 0
              GROUP BY tr.object_id HAVING COUNT(*) > 1) x"
        );
        $multi_mes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM (SELECT tr.object_id FROM {$wpdb->term_relationships} tr
              JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
              WHERE tt.taxonomy='mes_de_lancamento'
              GROUP BY tr.object_id HAVING COUNT(*) > 1) x"
        );
        $ja_migrados = 0;
        $tabela_midis = $wpdb->prefix . CENTRALMIDI_TABLE;
        if (CentralMidi_DB::table_exists($tabela_midis)) {
            $ja_migrados = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabela_midis}");
        }

        wp_send_json_success(array(
            'produtos'       => $produtos,
            'artistas'       => $artistas,
            'artistas_foto'  => $artistas_foto,
            'generos'        => $generos,
            'rlm'            => $rlm_map,
            'rlm_com'        => $com_rlm,
            'rlm_sem'        => $sem_rlm,
            'rlm_sem_mapear' => $rlm_outras,
            'com_demo'       => $com_demo,
            'demo_duplo'     => $demo_duplo,
            'multi_artista'  => $multi_artista,
            'multi_mes'      => $multi_mes,
            'ja_migrados'    => $ja_migrados,
            'conflitos'      => $this->conflict_log(),
        ));
    }

    /* ------------------------------------------------------------------
     * AJAX: prepare reference tables
     * ---------------------------------------------------------------- */

    public function handle_prepare() {
        $this->guard();
        global $wpdb;

        $artista_map = array();
        $criados     = 0;

        $rows = $wpdb->get_results(
            "SELECT t.term_id, t.name,
                    (SELECT tm.meta_value FROM {$wpdb->termmeta} tm
                      WHERE tm.term_id = t.term_id AND tm.meta_key='thumbnail_id' LIMIT 1) AS foto
             FROM {$wpdb->terms} t
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
             WHERE tt.taxonomy='product_cat' AND tt.parent <> 0
             ORDER BY t.term_id ASC"
        );

        foreach ($rows as $r) {
            $nome = sanitize_text_field($r->name);
            if ('' === $nome) {
                continue;
            }
            $foto = absint($r->foto);
            $aid  = CentralMidi_DB::add_artista($nome, $foto);
            if ($aid) {
                $artista_map[(int) $r->term_id] = (int) $aid;
                $criados++;
            }
        }

        $genero_map = array();
        $genres = $wpdb->get_results(
            "SELECT t.term_id, t.name FROM {$wpdb->terms} t
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
             WHERE tt.taxonomy='genero_musical' ORDER BY t.term_id ASC"
        );
        foreach ($genres as $r) {
            $nome = sanitize_text_field($r->name);
            if ('' === $nome) {
                continue;
            }
            $gid = CentralMidi_DB::add_genero($nome);
            if ($gid) {
                $genero_map[(int) $r->term_id] = (int) $gid;
            }
        }

        update_option(self::MAPS_OPTION, array(
            'artista' => $artista_map,
            'genero'  => $genero_map,
        ), false);

        $produtos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'"
        );

        wp_send_json_success(array(
            'artistas' => count($artista_map),
            'generos'  => count($genero_map),
            'processados' => $criados,
            'produtos' => $produtos,
        ));
    }

    /* ------------------------------------------------------------------
     * AJAX: migrate one batch
     * ---------------------------------------------------------------- */

    public function handle_batch() {
        $this->guard();
        global $wpdb;

        $maps = get_option(self::MAPS_OPTION, array());
        if (empty($maps['artista'])) {
            wp_send_json_error(array('message' => __('Execute a preparação antes.', 'centralmidi')));
        }

        $state = get_option(self::STATE_OPTION, array());
        $after = isset($state['last_id']) ? (int) $state['last_id'] : 0;

        $limit = isset($_POST['batch_size'])
            ? max(1, min(1000, absint($_POST['batch_size'])))
            : self::BATCH_SIZE;

        // Keyset pagination: `p.ID > $after` stays fast on 80k+ rows, unlike OFFSET.
        $products = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_type='product' AND post_status='publish' AND ID > %d
                 ORDER BY ID ASC LIMIT %d",
                $after,
                $limit
            )
        );
        $products = array_map('absint', $products);

        if (empty($products)) {
            $state['done'] = true;
            update_option(self::STATE_OPTION, $state, false);
            wp_send_json_success(array(
                'done'     => true,
                'processed' => isset($state['processed']) ? (int) $state['processed'] : 0,
                'conflitos' => $this->conflict_log(),
            ));
        }

        $ids       = implode(',', $products);
        $conflicts = $this->process_products($products, $ids, $maps);

        $state = get_option(self::STATE_OPTION, array());
        $state['last_id']    = (int) end($products);
        $state['processed']  = (isset($state['processed']) ? (int) $state['processed'] : 0) + count($products);
        $state['done']       = false;
        $state['updated']    = time();
        if (empty($state['started'])) {
            $state['started'] = time();
        }
        update_option(self::STATE_OPTION, $state, false);

        $this->merge_conflicts($conflicts);

        wp_send_json_success(array(
            'done'      => false,
            'last_id'   => $state['last_id'],
            'processed' => $state['processed'],
            'batch'     => count($products),
            'stats'     => $this->batch_stats,
        ));
    }

    /**
     * Human-readable labels for every conflict type the importer can emit.
     * @var array
     */
    private static $conflict_labels = array(
        'meta_duplicada'   => array('Meta duplicada', 'O produto tem mais de um valor para a mesma meta; foi mantido o primeiro (menor meta_id).'),
        'rlm_desconhecido' => array('Classificação desconhecida', 'O campo `rlm` tem um valor fora de "Melodia", "Letra" e "Letra | Melodia"; foi salvo como vazio.'),
        'multi_artista'    => array('Vários artistas', 'O produto está em mais de um artista; foi mantido o primeiro (menor term_id).'),
        'multi_genero'     => array('Vários gêneros', 'O produto tem mais de um gênero; foi mantido o primeiro.'),
        'multi_mes'        => array('Vários meses', 'O produto tem mais de um mês de lançamento; foi mantido o primeiro.'),
        'mes_ilegivel'     => array('Mês ilegível', 'Não foi possível interpretar o slug do mês/ano.'),
    );

    /**
     * Read the stored conflict log and decorate it for display.
     *
     * @return array{tipos: array, amostras: array} Counters + samples, ready for JSON.
     */
    public function conflict_log() {
        $log = get_option(self::CONFLICT_OPTION, array());
        $tipos = isset($log['tipos']) ? (array) $log['tipos'] : array();
        $amostras = isset($log['amostras']) ? (array) $log['amostras'] : array();
        arsort($tipos);

        $linhas = array();
        foreach ($tipos as $tipo => $n) {
            $meta = isset(self::$conflict_labels[$tipo]) ? self::$conflict_labels[$tipo] : array($tipo, '');
            $amostra = isset($amostras[$tipo]) ? array_slice((array) $amostras[$tipo], 0, 10) : array();
            $linhas[] = array(
                'tipo'    => $tipo,
                'rotulo'  => $meta[0],
                'ajuda'   => $meta[1],
                'n'       => (int) $n,
                'amostra' => $amostra,
            );
        }

        return array(
            'tipos'    => $linhas,
            'total'    => array_sum($tipos),
            'truncado' => count($amostras) ? (max(array_map('count', $amostras)) >= self::CONFLICT_SAMPLE) : false,
        );
    }

    /**
     * Per-batch counters, reset by process_products().
     * @var array
     */
    private $batch_stats = array();

    /**
     * Write the plugin row + `_centralmidi_*` meta for a set of products.
     */
    private function process_products($products, $ids, $maps) {
        global $wpdb;

        $this->batch_stats = array(
            'linhas'  => 0, 'artista' => 0, 'genero' => 0,
            'mes' => 0, 'classificacao' => 0, 'demo' => 0, 'sem_artista' => 0,
        );
        $conflicts = array();

        // --- artists (children of product_cat) -----------------------------
        $artista_por_produto = array();
        $rows = $wpdb->get_results(
            "SELECT tr.object_id, t.term_id, t.name
             FROM {$wpdb->term_relationships} tr
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
             WHERE tt.taxonomy='product_cat' AND tt.parent <> 0 AND tr.object_id IN ({$ids})
             ORDER BY tr.object_id, t.term_id"
        );
        foreach ($rows as $r) {
            $pid = (int) $r->object_id;
            if (isset($artista_por_produto[$pid])) {
                $conflicts[] = array('tipo' => 'multi_artista', 'produto' => $pid, 'detalhe' => $r->name);
                continue;
            }
            $artista_por_produto[$pid] = array(
                'term_id' => (int) $r->term_id,
                'nome'    => $r->name,
            );
        }

        // --- genres ---------------------------------------------------------
        $genero_por_produto = array();
        $rows = $wpdb->get_results(
            "SELECT tr.object_id, t.term_id, t.name
             FROM {$wpdb->term_relationships} tr
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
             WHERE tt.taxonomy='genero_musical' AND tr.object_id IN ({$ids})
             ORDER BY tr.object_id, t.term_id"
        );
        foreach ($rows as $r) {
            $pid = (int) $r->object_id;
            if (isset($genero_por_produto[$pid])) {
                $conflicts[] = array('tipo' => 'multi_genero', 'produto' => $pid, 'detalhe' => $r->name);
                continue;
            }
            $genero_por_produto[$pid] = array('term_id' => (int) $r->term_id, 'nome' => $r->name);
        }

        // --- release month ---------------------------------------------------
        $mes_por_produto = array();
        $rows = $wpdb->get_results(
            "SELECT tr.object_id, t.slug, t.name
             FROM {$wpdb->term_relationships} tr
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
             WHERE tt.taxonomy='mes_de_lancamento' AND tr.object_id IN ({$ids})
             ORDER BY tr.object_id, t.term_id"
        );
        foreach ($rows as $r) {
            $pid = (int) $r->object_id;
            list($mes, $ano) = self::parse_mes_ano($r->slug, $r->name);
            if (isset($mes_por_produto[$pid])) {
                $conflicts[] = array('tipo' => 'multi_mes', 'produto' => $pid, 'detalhe' => $r->name);
                continue;
            }
            if (!$mes) {
                $conflicts[] = array('tipo' => 'mes_ilegivel', 'produto' => $pid, 'detalhe' => $r->name);
            }
            $mes_por_produto[$pid] = array('mes' => $mes, 'ano' => $ano);
        }

        // --- legacy postmeta --------------------------------------------------
        $meta = array();
        $rows = $wpdb->get_results(
            "SELECT post_id, meta_key, meta_value
             FROM {$wpdb->postmeta}
             WHERE post_id IN ({$ids}) AND meta_key IN ('rlm', 'url_demo', '_thumbnail_id')
             ORDER BY post_id, meta_key, meta_id"
        );
        foreach ($rows as $r) {
            $pid = (int) $r->post_id;
            if (!isset($meta[$pid])) {
                $meta[$pid] = array();
            }
            $key = $r->meta_key;
            if (!isset($meta[$pid][$key])) {
                $meta[$pid][$key] = $r->meta_value;
            } else {
                $conflicts[] = array('tipo' => 'meta_duplicada', 'produto' => $pid, 'detalhe' => $key);
            }
        }

        // --- write ------------------------------------------------------------
        // Bulk SQL instead of upsert()/update_post_meta() per product: with ~80k
        // products the row-by-row API costs ~10k queries per batch and the run
        // degrades badly as wp_postmeta grows. Both statements below are
        // idempotent, so an interrupted run can simply be repeated.
        $midis_rows = array();
        $meta_rows  = array();
        $now        = current_time('mysql');

        foreach ($products as $pid) {
            $pm  = isset($meta[$pid]) ? $meta[$pid] : array();
            $art = isset($artista_por_produto[$pid]) ? $artista_por_produto[$pid] : null;
            $gen = isset($genero_por_produto[$pid]) ? $genero_por_produto[$pid] : null;
            $mae = isset($mes_por_produto[$pid]) ? $mes_por_produto[$pid] : array('mes' => 0, 'ano' => 0);

            $artista_id   = ($art && isset($maps['artista'][$art['term_id']])) ? (int) $maps['artista'][$art['term_id']] : 0;
            $genero_id    = ($gen && isset($maps['genero'][$gen['term_id']])) ? (int) $maps['genero'][$gen['term_id']] : 0;
            $artista_nome = $art ? $art['nome'] : '';
            $genero_nome  = $gen ? $gen['nome'] : '';

            if ($artista_id) {
                $this->batch_stats['artista']++;
            } else {
                $this->batch_stats['sem_artista']++;
            }
            if ($genero_id)    { $this->batch_stats['genero']++; }
            if ($mae['mes'])    { $this->batch_stats['mes']++; }

            // The legacy `rlm` field is free text ("Letra | Melodia"), so it has
            // to be translated. Unrecognised values are reported, never guessed.
            $raw_rlm = isset($pm['rlm']) ? $pm['rlm'] : '';
            list($classificacao, $rlm_ok) = self::map_classificacao($raw_rlm);
            if ($classificacao) {
                $this->batch_stats['classificacao']++;
            }
            if (!$rlm_ok) {
                $conflicts[] = array(
                    'tipo'    => 'rlm_desconhecido',
                    'produto' => $pid,
                    'detalhe' => $raw_rlm,
                );
            }

            $demo = isset($pm['url_demo']) ? trim((string) $pm['url_demo']) : '';
            if ($demo) {
                $this->batch_stats['demo']++;
            }

            $midis_rows[] = $wpdb->prepare(
                '(%d,%d,%d,%d,%d,%s,%d,%s,%s)',
                $pid, $artista_id, $genero_id, (int) $mae['mes'], (int) $mae['ano'],
                $classificacao, 1, $now, $now
            );

            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_artista', $artista_nome);
            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_artista_id', (string) $artista_id);
            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_genero', $genero_nome);
            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_genero_id', (string) $genero_id);
            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_mes_lancamento', (string) (int) $mae['mes']);
            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_ano_lancamento', (string) (int) $mae['ano']);
            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_classificacao', $classificacao);
            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_publicado', '1');
            $meta_rows[] = $wpdb->prepare('(%d,%s,%s)', $pid, '_centralmidi_demo_audio', $demo);

            $this->batch_stats['linhas']++;
        }

        $this->write_midis_bulk($midis_rows);
        $this->write_meta_bulk($products, $meta_rows);

        return $conflicts;
    }

    /**
     * Insert one batch of `wp_centralmidi_midis` rows.
     *
     * Relies on the UNIQUE key on `product_id`; `created_at` is preserved on
     * update so re-running does not rewrite history.
     *
     * @param string[] $rows Pre-formatted "(...)" tuples.
     */
    private function write_midis_bulk($rows) {
        global $wpdb;

        if (empty($rows)) {
            return;
        }

        $table = $wpdb->prefix . CENTRALMIDI_TABLE;
        $cols  = 'product_id,artista_id,genero_id,mes_lancamento,ano_lancamento,classificacao,publicado,created_at,updated_at';
        $upd   = 'artista_id=VALUES(artista_id),genero_id=VALUES(genero_id),'
               . 'mes_lancamento=VALUES(mes_lancamento),ano_lancamento=VALUES(ano_lancamento),'
               . 'classificacao=VALUES(classificacao),publicado=VALUES(publicado),'
               . 'updated_at=VALUES(updated_at)';

        foreach (array_chunk($rows, 200) as $chunk) {
            $sql = "INSERT INTO {$table} ({$cols}) VALUES " . implode(',', $chunk)
                 . " ON DUPLICATE KEY UPDATE {$upd}";
            $wpdb->query($sql);
        }
    }

    /**
     * Replace the importer-owned postmeta for a batch of products.
     *
     * wp_postmeta has no unique key on (post_id, meta_key), so the rows are
     * deleted first and re-inserted — which also keeps re-runs idempotent.
     *
     * @param int[]    $ids  Product IDs in this batch.
     * @param string[] $rows Pre-formatted "(post_id, meta_key, meta_value)" tuples.
     */
    private function write_meta_bulk($ids, $rows) {
        global $wpdb;

        if (empty($ids) || empty($rows)) {
            return;
        }

        $in   = implode(',', array_map('absint', $ids));
        $keys = "'" . implode("','", self::META_KEYS) . "'";
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$in}) AND meta_key IN ({$keys})"
        );

        foreach (array_chunk($rows, 500) as $chunk) {
            $wpdb->query(
                "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES "
                . implode(',', $chunk)
            );
        }
    }

    /* ------------------------------------------------------------------
     * Conflict log
     * ---------------------------------------------------------------- */

    private function merge_conflicts($conflicts) {
        if (empty($conflicts)) {
            return;
        }
        $log = get_option(self::CONFLICT_OPTION, array());
        if (!isset($log['tipos']) || !is_array($log['tipos'])) {
            $log = array('tipos' => array(), 'amostras' => array());
        }
        foreach ($conflicts as $c) {
            $tipo = $c['tipo'];
            if (!isset($log['tipos'][$tipo])) {
                $log['tipos'][$tipo] = 0;
                $log['amostras'][$tipo] = array();
            }
            $log['tipos'][$tipo]++;
            if (count($log['amostras'][$tipo]) < self::CONFLICT_SAMPLE) {
                $log['amostras'][$tipo][] = array(
                    'produto' => (int) $c['produto'],
                    'detalhe' => mb_substr((string) $c['detalhe'], 0, 120),
                );
            }
        }
        update_option(self::CONFLICT_OPTION, $log, false);
    }

    public function handle_reset() {
        $this->guard();

        global $wpdb;
        $tabela = $wpdb->prefix . CENTRALMIDI_TABLE;

        // Only the plugin's own tables/rows are cleared; legacy data is untouched.
        $wpdb->query("DELETE FROM {$tabela}");
        $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             WHERE pm.meta_key LIKE '\_centralmidi\_%'"
        );

        delete_option(self::MAPS_OPTION);
        delete_option(self::STATE_OPTION);
        delete_option(self::CONFLICT_OPTION);

        wp_send_json_success(array('message' => __('Progresso zerado.', 'centralmidi')));
    }

    /* ------------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    /**
     * Translate the legacy free-text `rlm` value into M / L / RLM.
     *
     * @return array [code, recognised]
     */
    public static function map_classificacao($raw) {
        $v = strtolower(trim((string) $raw));
        if ('' === $v) {
            return array('', true);
        }
        $v = str_replace(array('ç', 'ã', 'á', 'é', 'í', 'ó', 'ú', 'â', 'ê', 'ô'), 'caaeiouaeo', $v);

        $letra   = (false !== strpos($v, 'letra'));
        $melodia = (false !== strpos($v, 'melodia'));

        if ($letra && $melodia) {
            return array('RLM', true);
        }
        if ($letra) {
            return array('L', true);
        }
        if ($melodia) {
            return array('M', true);
        }
        return array('', false);
    }

    /**
     * Parse a legacy `mes_de_lancamento` term (`set-2025` / `SET 2025`).
     *
     * @return array [month 1-12, year]
     */
    public static function parse_mes_ano($slug, $name) {
        $src = strtolower(trim((string) $slug));
        if ('' === $src) {
            $src = strtolower(trim((string) $name));
        }
        $src = str_replace(array('ç', 'ã', 'á', 'é', 'í', 'ó', 'ú'), 'caaeiou', $src);

        if (preg_match('/([a-z]{3,})[^0-9]{0,4}([0-9]{4})/', $src, $m)) {
            $abbr = substr($m[1], 0, 3);
            if (isset(self::MESES[$abbr])) {
                return array(self::MESES[$abbr], (int) $m[2]);
            }
        }
        return array(0, 0);
    }

    private function guard() {
        check_ajax_referer('centralmidi_migration', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissão negada.', 'centralmidi')));
        }
    }
}
