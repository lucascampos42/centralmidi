<?php
/**
 * Central MIDI Admin: metabox on WooCommerce products + CRUD of reference lists.
 *
 * Fields: artista, genero, mes_lancamento, classificacao, demo_audio.
 */

defined('ABSPATH') || exit;

class CentralMidi_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post_product', array($this, 'save_meta_box'));

        add_action('wp_ajax_centralmidi_midis_table', array($this, 'handle_midis_table_ajax'));
        add_action('wp_ajax_centralmidi_midis_save', array($this, 'handle_midis_save_ajax'));
        add_action('wp_ajax_centralmidi_midis_bulk', array($this, 'handle_midis_bulk_ajax'));
    }

    public function enqueue_admin_assets($hook) {
        if (in_array($hook, array('toplevel_page_centralmidi', 'centralmidi_page_centralmidi-artistas', 'centralmidi_page_centralmidi-generos'), true)) {
            wp_enqueue_media();
            wp_enqueue_script(
                'centralmidi-admin',
                CENTRALMIDI_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                CENTRALMIDI_VERSION,
                true
            );
        }

        if ('centralmidi_page_centralmidi-midis' === $hook) {
            wp_enqueue_style(
                'centralmidi-tabulator',
                CENTRALMIDI_PLUGIN_URL . 'assets/vendor/tabulator/tabulator.min.css',
                array(),
                '6.5.2'
            );
            wp_enqueue_script(
                'centralmidi-tabulator',
                CENTRALMIDI_PLUGIN_URL . 'assets/vendor/tabulator/tabulator.min.js',
                array(),
                '6.5.2',
                true
            );
            wp_enqueue_style(
                'centralmidi-admin-midis',
                CENTRALMIDI_PLUGIN_URL . 'assets/css/admin-midis.css',
                array('centralmidi-tabulator'),
                CENTRALMIDI_VERSION
            );
            wp_enqueue_script(
                'centralmidi-midis-table',
                CENTRALMIDI_PLUGIN_URL . 'assets/js/midis-table.js',
                array('centralmidi-tabulator', 'jquery'),
                CENTRALMIDI_VERSION,
                true
            );
            wp_localize_script('centralmidi-midis-table', 'CentralMidiMidis', array(
                'ajaxUrl'         => admin_url('admin-ajax.php'),
                'nonce'           => wp_create_nonce('centralmidi_ajax'),
                'artistas'        => array_map(function ($a) {
                    return array('id' => (int) $a->id, 'nome' => $a->nome);
                }, CentralMidi_DB::get_artistas()),
                'generos'         => array_map(function ($g) {
                    return array('id' => (int) $g->id, 'nome' => $g->nome);
                }, CentralMidi_DB::get_generos()),
                'meses'           => array(
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
                ),
                'classificacoes'  => array(
                    'M'   => __('Melodia', 'centralmidi'),
                    'L'   => __('Letra', 'centralmidi'),
                    'RLM' => __('Melodia + Letra', 'centralmidi'),
                ),
                'textos'          => array(
                    'semArquivo' => __('Sem arquivo — clique para definir', 'centralmidi'),
                    'erro'       => __('Erro ao salvar.', 'centralmidi'),
                    'selecione'  => __('Selecione ao menos um MIDI.', 'centralmidi'),
                    'acao'       => __('Selecione uma ação em lote.', 'centralmidi'),
                    'confirmar'  => __('Remover metadados MIDI dos selecionados?', 'centralmidi'),
                    'atualizado' => __('MIDI(s) atualizados.', 'centralmidi'),
                ),
            ));
        }
    }

    public function register_menu() {
        add_menu_page(
            __('Central MIDI', 'centralmidi'),
            __('Central MIDI', 'centralmidi'),
            'manage_options',
            'centralmidi',
            array($this, 'render_artistas_page'),
            'dashicons-format-audio',
            56
        );

        add_submenu_page(
            'centralmidi',
            __('MIDIs', 'centralmidi'),
            __('MIDIs', 'centralmidi'),
            'manage_options',
            'centralmidi-midis',
            array($this, 'render_midis_page')
        );

        add_submenu_page(
            'centralmidi',
            __('Artistas', 'centralmidi'),
            __('Artistas', 'centralmidi'),
            'manage_options',
            'centralmidi-artistas',
            array($this, 'render_artistas_page')
        );

        add_submenu_page(
            'centralmidi',
            __('Gêneros', 'centralmidi'),
            __('Gêneros', 'centralmidi'),
            'manage_options',
            'centralmidi-generos',
            array($this, 'render_generos_page')
        );
    }

    /**
     * Config of a reference list (artista | genero).
     */
    private function referencia_config($kind) {
        $configs = array(
            'artista' => array(
                'page'     => 'centralmidi-artistas',
                'title'    => __('Central MIDI — Artistas', 'centralmidi'),
                'desc'     => __('Cadastre aqui os artistas/bandas disponíveis. Eles aparecem como select na edição dos produtos MIDI.', 'centralmidi'),
                'add_text' => __('Adicionar Artista', 'centralmidi'),
                'edit_text' => __('Editar Artista', 'centralmidi'),
                'list_title' => __('Artistas Cadastrados', 'centralmidi'),
                'placeholder' => __('Nome do artista/banda', 'centralmidi'),
                'empty'    => __('Nenhum artista cadastrado.', 'centralmidi'),
                'confirm'  => __('Excluir este artista?', 'centralmidi'),
                'added'    => __('Artista adicionado com sucesso.', 'centralmidi'),
                'updated'  => __('Artista atualizado com sucesso.', 'centralmidi'),
                'updated_err' => __('Não foi possível atualizar (nome duplicado ou vazio).', 'centralmidi'),
                'removed'  => __('Artista removido.', 'centralmidi'),
                'empty_err' => __('Informe o nome do artista.', 'centralmidi'),
                'prefix'   => 'artista',
                'has_foto' => true,
                'get_all'  => 'get_artistas',
                'get_one'  => 'get_artista',
                'add'      => 'add_artista',
                'update'   => 'update_artista',
                'delete'   => 'delete_artista',
            ),
            'genero' => array(
                'page'     => 'centralmidi-generos',
                'title'    => __('Central MIDI — Gêneros', 'centralmidi'),
                'desc'     => __('Cadastre aqui os gêneros musicais disponíveis. Eles aparecem como select na edição dos produtos MIDI.', 'centralmidi'),
                'add_text' => __('Adicionar Gênero', 'centralmidi'),
                'edit_text' => __('Editar Gênero', 'centralmidi'),
                'list_title' => __('Gêneros Cadastrados', 'centralmidi'),
                'placeholder' => __('Nome do gênero musical', 'centralmidi'),
                'empty'    => __('Nenhum gênero cadastrado.', 'centralmidi'),
                'confirm'  => __('Excluir este gênero?', 'centralmidi'),
                'added'    => __('Gênero adicionado com sucesso.', 'centralmidi'),
                'updated'  => __('Gênero atualizado com sucesso.', 'centralmidi'),
                'updated_err' => __('Não foi possível atualizar (nome duplicado ou vazio).', 'centralmidi'),
                'removed'  => __('Gênero removido.', 'centralmidi'),
                'empty_err' => __('Informe o nome do gênero.', 'centralmidi'),
                'prefix'   => 'genero',
                'get_all'  => 'get_generos',
                'get_one'  => 'get_genero',
                'add'      => 'add_genero',
                'update'   => 'update_genero',
                'delete'   => 'delete_genero',
            ),
        );
        return $configs[$kind] ?? null;
    }

    /**
     * Handle add/update/delete POST/GET actions for a reference list.
     */
    private function handle_referencia_actions($cfg) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $prefix = $cfg['prefix'];
        $nonce  = 'centralmidi_' . $prefix . 's';
        $delete_nonce = 'centralmidi_delete_' . $prefix;

        if (isset($_POST['centralmidi_add_' . $prefix]) && check_admin_referer($nonce)) {
            $nome = sanitize_text_field(wp_unslash($_POST['centralmidi_novo_' . $prefix] ?? ''));
            if ('' !== $nome) {
                $foto_id = absint($_POST['centralmidi_' . $prefix . '_foto'] ?? 0);
                if (!empty($cfg['has_foto'])) {
                    $id = CentralMidi_DB::{$cfg['add']}($nome, $foto_id);
                } else {
                    $id = CentralMidi_DB::{$cfg['add']}($nome);
                }
                $this->set_notice($id ? 'success' : 'error', $id ? $cfg['added'] : $cfg['empty_err']);
            } else {
                $this->set_notice('error', $cfg['empty_err']);
            }
        }

        if (isset($_POST['centralmidi_update_' . $prefix]) && check_admin_referer($nonce)) {
            $id   = absint($_POST['centralmidi_' . $prefix . '_id'] ?? 0);
            $nome = sanitize_text_field(wp_unslash($_POST['centralmidi_' . $prefix . '_nome'] ?? ''));
            if (!empty($cfg['has_foto'])) {
                $foto_id = absint($_POST['centralmidi_' . $prefix . '_foto'] ?? 0);
                $ok      = CentralMidi_DB::{$cfg['update']}($id, $nome, $foto_id);
            } else {
                $ok = CentralMidi_DB::{$cfg['update']}($id, $nome);
            }
            if ($ok) {
                $this->set_notice('success', $cfg['updated']);
            } else {
                $this->set_notice('error', $cfg['updated_err']);
            }
        }

        if (isset($_GET['centralmidi_delete_' . $prefix]) && check_admin_referer($delete_nonce)) {
            CentralMidi_DB::{$cfg['delete']}(absint($_GET['centralmidi_delete_' . $prefix]));
            $this->set_notice('success', $cfg['removed']);
        }
    }

    private function set_notice($type, $message) {
        $notices   = get_option('centralmidi_admin_notices', array());
        $notices[] = array('type' => $type, 'message' => $message);
        update_option('centralmidi_admin_notices', $notices);
    }

    private function render_notices() {
        $notices = get_option('centralmidi_admin_notices', array());
        if (empty($notices)) {
            return;
        }
        foreach ($notices as $notice) {
            $class = 'updated' === $notice['type'] ? 'notice-success' : 'notice-error';
            printf(
                '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr($class),
                esc_html($notice['message'])
            );
        }
        delete_option('centralmidi_admin_notices');
    }

    private function render_referencia_page($kind) {
        $cfg = $this->referencia_config($kind);
        if (!$cfg) {
            return;
        }

        $this->handle_referencia_actions($cfg);

        $prefix = $cfg['prefix'];
        $items  = CentralMidi_DB::{$cfg['get_all']}();
        $editing = null;
        if (isset($_GET['centralmidi_edit_' . $prefix])) {
            $editing = CentralMidi_DB::{$cfg['get_one']}(absint($_GET['centralmidi_edit_' . $prefix]));
        }

        $foto_id      = $editing ? (int) $editing->foto_id : 0;
        $foto_preview = $foto_id ? wp_get_attachment_image_url($foto_id, 'thumbnail') : '';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($cfg['title']); ?></h1>
            <p><?php echo esc_html($cfg['desc']); ?></p>

            <?php $this->render_notices(); ?>

            <h2><?php echo $editing ? esc_html($cfg['edit_text']) : esc_html($cfg['add_text']); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . $cfg['page'])); ?>" style="max-width: 420px;">
                <?php wp_nonce_field('centralmidi_' . $prefix . 's'); ?>
                <?php if ($editing) : ?>
                    <input type="hidden" name="centralmidi_<?php echo esc_attr($prefix); ?>_id" value="<?php echo esc_attr($editing->id); ?>" />
                    <p>
                        <input type="text" name="centralmidi_<?php echo esc_attr($prefix); ?>_nome" value="<?php echo esc_attr($editing->nome); ?>" class="regular-text" style="width:100%;" />
                    </p>
                    <?php $this->render_foto_field($cfg, $foto_id, $foto_preview); ?>
                    <p>
                        <button type="submit" name="centralmidi_update_<?php echo esc_attr($prefix); ?>" class="button button-primary"><?php esc_html_e('Salvar', 'centralmidi'); ?></button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . $cfg['page'])); ?>" class="button"><?php esc_html_e('Cancelar', 'centralmidi'); ?></a>
                    </p>
                <?php else : ?>
                    <p>
                        <input type="text" name="centralmidi_novo_<?php echo esc_attr($prefix); ?>" placeholder="<?php echo esc_attr($cfg['placeholder']); ?>" class="regular-text" style="width:100%;" />
                    </p>
                    <?php $this->render_foto_field($cfg, $foto_id, $foto_preview); ?>
                    <p>
                        <button type="submit" name="centralmidi_add_<?php echo esc_attr($prefix); ?>" class="button button-primary"><?php esc_html_e('Adicionar', 'centralmidi'); ?></button>
                    </p>
                <?php endif; ?>
            </form>

            <hr />

            <h2><?php echo esc_html($cfg['list_title']); ?> (<?php echo count($items); ?>)</h2>
            <table class="widefat striped" style="max-width: 720px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nome', 'centralmidi'); ?></th>
                        <th style="width: 180px;"><?php esc_html_e('Ações', 'centralmidi'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$items) : ?>
                        <tr><td colspan="2"><?php echo esc_html($cfg['empty']); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <tr>
                                <td><?php echo esc_html($item->nome); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $cfg['page'] . '&centralmidi_edit_' . $prefix . '=' . $item->id)); ?>" class="button button-small"><?php esc_html_e('Editar', 'centralmidi'); ?></a>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=' . $cfg['page'] . '&centralmidi_delete_' . $prefix . '=' . $item->id), 'centralmidi_delete_' . $prefix)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js($cfg['confirm']); ?>');"><?php esc_html_e('Excluir', 'centralmidi'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Media-picker field for the artist photo (used only when config has_foto).
     */
    private function render_foto_field($cfg, $foto_id, $foto_preview) {
        if (empty($cfg['has_foto'])) {
            return;
        }
        $prefix = $cfg['prefix'];
        ?>
        <div class="cm-artist-foto-field">
            <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Foto do Artista', 'centralmidi'); ?></label>
            <p class="description" style="margin-top:0;"><?php esc_html_e('Usada como imagem do produto quando o MIDI não tiver foto própria.', 'centralmidi'); ?></p>
            <div style="margin: 10px 0;">
                <img id="centralmidi_<?php echo esc_attr($prefix); ?>_foto_preview"
                     src="<?php echo esc_url($foto_preview); ?>"
                     style="max-width:120px; max-height:120px; border-radius:8px; border:1px solid #cbd5e1; <?php echo $foto_preview ? '' : 'display:none;'; ?>"
                     alt="" />
            </div>
            <input type="hidden" id="centralmidi_<?php echo esc_attr($prefix); ?>_foto" name="centralmidi_<?php echo esc_attr($prefix); ?>_foto" value="<?php echo esc_attr($foto_id); ?>" />
            <p style="margin:0;">
                <button type="button" id="cm-artista-foto-choose" class="button"><?php esc_html_e('Selecionar imagem', 'centralmidi'); ?></button>
                <button type="button" id="cm-artista-foto-remove" class="button"><?php esc_html_e('Remover', 'centralmidi'); ?></button>
            </p>
        </div>
        <?php
    }

    public function render_artistas_page() {
        $this->render_referencia_page('artista');
    }

    public function render_generos_page() {
        $this->render_referencia_page('genero');
    }

    /**
     * Admin page: list MIDIs with filters and bulk editing.
     */
    public function render_midis_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->handle_midis_bulk_action();

        $per_page = 20;
        $paged    = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);

        $filters = array(
            'busca'        => isset($_GET['busca']) ? sanitize_text_field(wp_unslash($_GET['busca'])) : '',
            'artista_id'   => isset($_GET['artista_id']) ? absint($_GET['artista_id']) : 0,
            'genero_id'    => isset($_GET['genero_id']) ? absint($_GET['genero_id']) : 0,
            'mes'          => isset($_GET['mes']) ? absint($_GET['mes']) : 0,
            'ano'          => isset($_GET['ano']) ? absint($_GET['ano']) : 0,
            'classificacao' => isset($_GET['classificacao']) ? sanitize_text_field(wp_unslash($_GET['classificacao'])) : '',
        );

        $total = 0;
        $midis = CentralMidi_DB::get_midis_admin($filters, $per_page, $paged, $total);

        $artistas    = CentralMidi_DB::get_artistas();
        $generos     = CentralMidi_DB::get_generos();
        $class_labels = array(
            'M'   => __('MIDI somente com Melodia', 'centralmidi'),
            'L'   => __('MIDI somente com Letra sincronizada', 'centralmidi'),
            'RLM' => __('MIDI com Melodia e Letra sincronizada', 'centralmidi'),
        );
        $meses = array(
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        );

        $current_year = (int) date('Y');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Central MIDI — MIDIs', 'centralmidi'); ?></h1>
            <p><?php esc_html_e('Lista os arquivos MIDI sincronizados na tabela do plugin, com filtros e edição em lote dos metadados.', 'centralmidi'); ?></p>

            <?php $this->render_notices(); ?>

            <form method="get" style="margin: 12px 0 20px;">
                <input type="hidden" name="page" value="centralmidi-midis" />
                <input type="search" name="busca" value="<?php echo esc_attr($filters['busca']); ?>" placeholder="<?php esc_attr_e('Buscar pelo nome do produto...', 'centralmidi'); ?>" style="min-width: 240px;" />
                <select name="artista_id">
                    <option value="0"><?php esc_html_e('Todos os artistas', 'centralmidi'); ?></option>
                    <?php foreach ($artistas as $a) : ?>
                        <option value="<?php echo esc_attr($a->id); ?>" <?php selected($filters['artista_id'], $a->id); ?>><?php echo esc_html($a->nome); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="genero_id">
                    <option value="0"><?php esc_html_e('Todos os gêneros', 'centralmidi'); ?></option>
                    <?php foreach ($generos as $g) : ?>
                        <option value="<?php echo esc_attr($g->id); ?>" <?php selected($filters['genero_id'], $g->id); ?>><?php echo esc_html($g->nome); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="mes">
                    <option value="0"><?php esc_html_e('Todos os meses', 'centralmidi'); ?></option>
                    <?php foreach ($meses as $num => $nome) : ?>
                        <option value="<?php echo esc_attr($num); ?>" <?php selected($filters['mes'], $num); ?>><?php echo esc_html($nome); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="ano">
                    <option value="0"><?php esc_html_e('Todos os anos', 'centralmidi'); ?></option>
                    <?php for ($y = $current_year; $y >= $current_year - 4; $y--) : ?>
                        <option value="<?php echo esc_attr($y); ?>" <?php selected($filters['ano'], $y); ?>><?php echo esc_html($y); ?></option>
                    <?php endfor; ?>
                </select>
                <select name="classificacao">
                    <option value=""><?php esc_html_e('Todas as classificações', 'centralmidi'); ?></option>
                    <?php foreach ($class_labels as $code => $label) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($filters['classificacao'], $code); ?>><?php echo esc_html('#' . $code); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php esc_html_e('Filtrar', 'centralmidi'); ?></button>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=centralmidi-midis')); ?>"><?php esc_html_e('Limpar', 'centralmidi'); ?></a>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=centralmidi-midis')); ?>">
                <?php wp_nonce_field('centralmidi_bulk_midis', 'centralmidi_midis_nonce'); ?>

                <div style="margin: 12px 0;">
                    <select name="cm_bulk_op">
                        <option value=""><?php esc_html_e('— Edição em lote —', 'centralmidi'); ?></option>
                        <optgroup label="<?php esc_attr_e('Definir artista', 'centralmidi'); ?>">
                            <?php foreach ($artistas as $a) : ?>
                                <option value="set_artista:<?php echo esc_attr($a->id); ?>"><?php echo esc_html($a->nome); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e('Definir gênero', 'centralmidi'); ?>">
                            <?php foreach ($generos as $g) : ?>
                                <option value="set_genero:<?php echo esc_attr($g->id); ?>"><?php echo esc_html($g->nome); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e('Definir mês de lançamento', 'centralmidi'); ?>">
                            <?php foreach ($meses as $num => $nome) : ?>
                                <option value="set_mes:<?php echo esc_attr($num); ?>"><?php echo esc_html($nome); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e('Definir ano de lançamento', 'centralmidi'); ?>">
                            <?php for ($y = $current_year; $y >= $current_year - 4; $y--) : ?>
                                <option value="set_ano:<?php echo esc_attr($y); ?>"><?php echo esc_html($y); ?></option>
                            <?php endfor; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e('Definir classificação', 'centralmidi'); ?>">
                            <?php foreach ($class_labels as $code => $label) : ?>
                                <option value="set_classificacao:<?php echo esc_attr($code); ?>"><?php echo esc_html('#' . $code); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e('Remover', 'centralmidi'); ?>">
                            <option value="delete"><?php esc_html_e('Remover metadados MIDI dos selecionados', 'centralmidi'); ?></option>
                        </optgroup>
                    </select>
                    <button type="submit" name="cm_bulk" value="apply" class="button"><?php esc_html_e('Aplicar aos selecionados', 'centralmidi'); ?></button>
                </div>

                <table class="widefat striped" style="max-width: 1100px;">
                    <thead>
                        <tr>
                            <td style="width: 28px;"><input type="checkbox" id="cm-select-all" /></td>
                            <th><?php esc_html_e('Produto', 'centralmidi'); ?></th>
                            <th><?php esc_html_e('Artista', 'centralmidi'); ?></th>
                            <th><?php esc_html_e('Gênero', 'centralmidi'); ?></th>
                            <th><?php esc_html_e('Lançamento', 'centralmidi'); ?></th>
                            <th><?php esc_html_e('Classificação', 'centralmidi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$midis) : ?>
                            <tr><td colspan="6"><?php esc_html_e('Nenhum MIDI encontrado com os filtros selecionados.', 'centralmidi'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($midis as $m) : ?>
                                <tr>
                                    <td><input type="checkbox" name="cm_midis[]" value="<?php echo esc_attr($m->product_id); ?>" /></td>
                                    <td>
                                        <strong><?php echo esc_html($m->titulo ? $m->titulo : '#' . $m->product_id); ?></strong>
                                        <div class="row-actions">
                                            <a href="<?php echo esc_url(get_edit_post_link($m->product_id)); ?>"><?php esc_html_e('Editar produto', 'centralmidi'); ?></a> |
                                            <a href="<?php echo esc_url(get_permalink($m->product_id)); ?>" target="_blank"><?php esc_html_e('Ver', 'centralmidi'); ?></a>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($m->artista ? $m->artista : '—'); ?></td>
                                    <td><?php echo esc_html($m->genero ? $m->genero : '—'); ?></td>
                                    <td><?php echo $m->mes_lancamento ? esc_html(($meses[$m->mes_lancamento] ?? '') . ' ' . $m->ano_lancamento) : '—'; ?></td>
                                    <td><?php echo $m->classificacao ? esc_html('#' . $m->classificacao) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div style="margin: 12px 0;">
                    <select name="cm_bulk_op">
                        <option value=""><?php esc_html_e('— Edição em lote —', 'centralmidi'); ?></option>
                        <?php foreach (array(
                            'artistas' => $artistas, 'generos' => $generos,
                        ) as $group => $items) : ?>
                            <optgroup label="<?php echo esc_attr($group === 'artistas' ? __('Definir artista', 'centralmidi') : __('Definir gênero', 'centralmidi')); ?>">
                                <?php foreach ($items as $item) : ?>
                                    <option value="<?php echo esc_attr($group === 'artistas' ? 'set_artista:' . $item->id : 'set_genero:' . $item->id); ?>"><?php echo esc_html($item->nome); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                        <optgroup label="<?php esc_attr_e('Definir mês de lançamento', 'centralmidi'); ?>">
                            <?php foreach ($meses as $num => $nome) : ?>
                                <option value="set_mes:<?php echo esc_attr($num); ?>"><?php echo esc_html($nome); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e('Definir ano de lançamento', 'centralmidi'); ?>">
                            <?php for ($y = $current_year; $y >= $current_year - 4; $y--) : ?>
                                <option value="set_ano:<?php echo esc_attr($y); ?>"><?php echo esc_html($y); ?></option>
                            <?php endfor; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e('Definir classificação', 'centralmidi'); ?>">
                            <?php foreach ($class_labels as $code => $label) : ?>
                                <option value="set_classificacao:<?php echo esc_attr($code); ?>"><?php echo esc_html('#' . $code); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e('Remover', 'centralmidi'); ?>">
                            <option value="delete"><?php esc_html_e('Remover metadados MIDI dos selecionados', 'centralmidi'); ?></option>
                        </optgroup>
                    </select>
                    <button type="submit" name="cm_bulk" value="apply" class="button"><?php esc_html_e('Aplicar aos selecionados', 'centralmidi'); ?></button>
                </div>

                <?php $this->render_midis_pagination($total, $per_page, $paged); ?>
            </form>

            <script>
            (function () {
                var selectAll = document.getElementById('cm-select-all');
                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        document.querySelectorAll('input[name="cm_midis[]"]').forEach(function (cb) {
                            cb.checked = selectAll.checked;
                        });
                    });
                }
            })();
            </script>
        </div>
        <?php
    }

    private function render_midis_pagination($total, $per_page, $paged) {
        $pages = (int) ceil($total / $per_page);
        if ($pages < 2) {
            return;
        }
        $args = array(
            'base'      => add_query_arg('paged', '%#%'),
            'format'    => '',
            'current'   => $paged,
            'total'     => $pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        );
        echo '<div class="tablenav" style="clear:both;margin-top:12px;">';
        echo '<div class="tablenav-pages">' . paginate_links($args) . '</div>';
        echo '<div class="displaying-num">' . sprintf(esc_html__('%d MIDIs', 'centralmidi'), (int) $total) . '</div>';
        echo '</div>';
    }

    /**
     * Apply the bulk edit operation to the selected product IDs (PRG: redirect after).
     */
    private function handle_midis_bulk_action() {
        if (empty($_POST['cm_bulk']) || 'apply' !== $_POST['cm_bulk']) {
            return;
        }
        if (!isset($_POST['centralmidi_midis_nonce']) || !wp_verify_nonce(wp_unslash($_POST['centralmidi_midis_nonce']), 'centralmidi_bulk_midis')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $ids = isset($_POST['cm_midis']) ? array_map('absint', (array) wp_unslash($_POST['cm_midis'])) : array();
        $op  = isset($_POST['cm_bulk_op']) ? sanitize_text_field(wp_unslash($_POST['cm_bulk_op'])) : '';

        if (!$ids || !$op) {
            $this->set_notice('error', __('Selecione ao menos um MIDI e uma ação em lote.', 'centralmidi'));
            $this->redirect_midis_back();
            return;
        }

        list($action, $value) = array_pad(explode(':', $op, 2), 2, '');

        $count = 0;
        switch ($action) {
            case 'set_artista':
                $value = absint($value);
                $artista = CentralMidi_DB::get_artista($value);
                foreach ($ids as $pid) {
                    update_post_meta($pid, '_centralmidi_artista_id', $value);
                    update_post_meta($pid, '_centralmidi_artista', $artista ? $artista->nome : '');
                    $this->upsert_product_meta($pid, array('artista_id' => $value));
                    $count++;
                }
                break;

            case 'set_genero':
                $value = absint($value);
                $genero = CentralMidi_DB::get_genero($value);
                foreach ($ids as $pid) {
                    update_post_meta($pid, '_centralmidi_genero_id', $value);
                    update_post_meta($pid, '_centralmidi_genero', $genero ? $genero->nome : '');
                    $this->upsert_product_meta($pid, array('genero_id' => $value));
                    $count++;
                }
                break;

            case 'set_mes':
                $value = absint($value);
                foreach ($ids as $pid) {
                    update_post_meta($pid, '_centralmidi_mes_lancamento', $value);
                    $this->upsert_product_meta($pid, array('mes_lancamento' => $value));
                    $count++;
                }
                break;

            case 'set_ano':
                $value = absint($value);
                foreach ($ids as $pid) {
                    update_post_meta($pid, '_centralmidi_ano_lancamento', $value);
                    $this->upsert_product_meta($pid, array('ano_lancamento' => $value));
                    $count++;
                }
                break;

            case 'set_classificacao':
                $value = CentralMidi_DB::sanitize_classificacao($value);
                foreach ($ids as $pid) {
                    update_post_meta($pid, '_centralmidi_classificacao', $value);
                    $this->upsert_product_meta($pid, array('classificacao' => $value));
                    $count++;
                }
                break;

            case 'delete':
                foreach ($ids as $pid) {
                    foreach (array(
                        '_centralmidi_artista', '_centralmidi_artista_id',
                        '_centralmidi_genero', '_centralmidi_genero_id',
                        '_centralmidi_mes_lancamento', '_centralmidi_ano_lancamento',
                        '_centralmidi_classificacao', '_centralmidi_demo_audio',
                    ) as $meta_key) {
                        delete_post_meta($pid, $meta_key);
                    }
                    CentralMidi_DB::delete($pid);
                    $count++;
                }
                break;

            default:
                $this->set_notice('error', __('Ação em lote inválida.', 'centralmidi'));
                $this->redirect_midis_back();
                return;
        }

        CentralMidi_DB::clear_home_cache();
        $this->set_notice('success', sprintf(__('%d MIDI(s) atualizados em lote.', 'centralmidi'), $count));
        $this->redirect_midis_back();
    }

    /**
     * Upsert a product row keeping the current values for fields not being changed.
     */
    private function upsert_product_meta($product_id, $override = array()) {
        $data = array(
            'artista_id'     => (int) get_post_meta($product_id, '_centralmidi_artista_id', true),
            'genero_id'      => (int) get_post_meta($product_id, '_centralmidi_genero_id', true),
            'mes_lancamento' => (int) get_post_meta($product_id, '_centralmidi_mes_lancamento', true),
            'ano_lancamento' => (int) get_post_meta($product_id, '_centralmidi_ano_lancamento', true),
            'classificacao'  => CentralMidi_DB::sanitize_classificacao(get_post_meta($product_id, '_centralmidi_classificacao', true)),
        );
        CentralMidi_DB::upsert($product_id, array_merge($data, $override));
    }

    private function redirect_midis_back() {
        $url = admin_url('admin.php?page=centralmidi-midis');
        wp_safe_redirect($url);
        exit;
    }

    public function add_meta_box() {
        add_meta_box(
            'centralmidi_metadados',
            __('Central MIDI — Dados do Arquivo & Classificação', 'centralmidi'),
            array($this, 'render'),
            'product',
            'normal',
            'high'
        );
    }

    public function render($post) {
        wp_nonce_field('centralmidi_save_metadados', 'centralmidi_metadados_nonce');

        $artista_id     = (int) get_post_meta($post->ID, '_centralmidi_artista_id', true);
        $artista        = get_post_meta($post->ID, '_centralmidi_artista', true);
        $genero_id      = (int) get_post_meta($post->ID, '_centralmidi_genero_id', true);
        $genero         = get_post_meta($post->ID, '_centralmidi_genero', true);
        $mes_lancamento = get_post_meta($post->ID, '_centralmidi_mes_lancamento', true);
        $ano_lancamento = get_post_meta($post->ID, '_centralmidi_ano_lancamento', true);
        $ano_lancamento = $ano_lancamento ? (int) $ano_lancamento : (int) date('Y');
        $classificacao  = get_post_meta($post->ID, '_centralmidi_classificacao', true);
        $classificacao  = CentralMidi_DB::sanitize_classificacao($classificacao);
        $demo_audio     = get_post_meta($post->ID, '_centralmidi_demo_audio', true);

        $artistas   = CentralMidi_DB::get_artistas();
        $generos    = CentralMidi_DB::get_generos();

        $meses = array(
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        );
        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 10px 0;">
            <div style="grid-column: 1 / -1;">
                <label for="centralmidi_demo_audio" style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Áudio Demo MP3 (URL de Demonstração)', 'centralmidi'); ?></label>
                <input type="url" id="centralmidi_demo_audio" name="_centralmidi_demo_audio" value="<?php echo esc_url($demo_audio); ?>" style="width:100%;" placeholder="https://exemplo.com/audios/demo-musica.mp3" />
                <p class="description"><?php esc_html_e('Insira o link direto do MP3 para permitir que os clientes ouçam a prévia no catálogo.', 'centralmidi'); ?></p>
            </div>

            <div>
                <label for="centralmidi_artista" style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Artista / Banda', 'centralmidi'); ?></label>
                <select id="centralmidi_artista" name="_centralmidi_artista_id" style="width:100%;">
                    <option value="0">— <?php esc_html_e('Selecione o artista', 'centralmidi'); ?> —</option>
                    <?php foreach ($artistas as $a) : ?>
                        <option value="<?php echo esc_attr($a->id); ?>" <?php selected($artista_id, $a->id); ?>><?php echo esc_html($a->nome); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php echo wp_kses_post(sprintf(
                        __('Selecione um artista cadastrado ou <a href="%s">gerencie a lista de artistas</a>.', 'centralmidi'),
                        esc_url(admin_url('admin.php?page=centralmidi-artistas'))
                    )); ?>
                </p>
                <input type="hidden" name="_centralmidi_artista" value="<?php echo esc_attr($artista); ?>" />
            </div>

            <div>
                <label for="centralmidi_genero" style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Gênero Musical', 'centralmidi'); ?></label>
                <select id="centralmidi_genero" name="_centralmidi_genero_id" style="width:100%;">
                    <option value="0">— <?php esc_html_e('Selecione o gênero', 'centralmidi'); ?> —</option>
                    <?php foreach ($generos as $g) : ?>
                        <option value="<?php echo esc_attr($g->id); ?>" <?php selected($genero_id, $g->id); ?>><?php echo esc_html($g->nome); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php echo wp_kses_post(sprintf(
                        __('Selecione um gênero cadastrado ou <a href="%s">gerencie a lista de gêneros</a>.', 'centralmidi'),
                        esc_url(admin_url('admin.php?page=centralmidi-generos'))
                    )); ?>
                </p>
                <input type="hidden" name="_centralmidi_genero" value="<?php echo esc_attr($genero); ?>" />
            </div>

            <div>
                <label for="centralmidi_mes" style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Mês de Lançamento no Site', 'centralmidi'); ?></label>
                <select id="centralmidi_mes" name="_centralmidi_mes_lancamento" style="width:100%;">
                    <option value="0">— Selecione o mês —</option>
                    <?php foreach ($meses as $num => $nome) : ?>
                        <option value="<?php echo esc_attr($num); ?>" <?php selected($mes_lancamento, $num); ?>><?php echo esc_html($nome); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="centralmidi_ano" style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Ano de Lançamento', 'centralmidi'); ?></label>
                <select id="centralmidi_ano" name="_centralmidi_ano_lancamento" style="width:100%;">
                    <option value="0">— Selecione o ano —</option>
                    <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 4; $y--) : ?>
                        <option value="<?php echo esc_attr($y); ?>" <?php selected($ano_lancamento, $y); ?>><?php echo esc_html($y); ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e('Classificação do MIDI', 'centralmidi'); ?></label>
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                    <label style="display:block;margin-bottom:6px;cursor:pointer;">
                        <input type="radio" name="_centralmidi_classificacao" value="M" <?php checked($classificacao, 'M'); ?>> 
                        <strong>#M</strong> — <?php esc_html_e('MIDI somente com Melodia', 'centralmidi'); ?>
                    </label>
                    <label style="display:block;margin-bottom:6px;cursor:pointer;">
                        <input type="radio" name="_centralmidi_classificacao" value="L" <?php checked($classificacao, 'L'); ?>> 
                        <strong>#L</strong> — <?php esc_html_e('MIDI somente com Letra sincronizada', 'centralmidi'); ?>
                    </label>
                    <label style="display:block;cursor:pointer;">
                        <input type="radio" name="_centralmidi_classificacao" value="RLM" <?php checked($classificacao, 'RLM'); ?>> 
                        <strong>#RLM</strong> — <?php esc_html_e('MIDI com Melodia e Letra sincronizada', 'centralmidi'); ?>
                    </label>
                </div>
            </div>
        </div>
        <p class="description"><?php esc_html_e('Os dados são salvos no produto WooCommerce e sincronizados na tabela wp_centralmidi_midis.', 'centralmidi'); ?></p>
        <?php
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['centralmidi_metadados_nonce']) || !wp_verify_nonce($_POST['centralmidi_metadados_nonce'], 'centralmidi_save_metadados')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $artista_id = isset($_POST['_centralmidi_artista_id']) ? absint($_POST['_centralmidi_artista_id']) : 0;
        $genero_id  = isset($_POST['_centralmidi_genero_id']) ? absint($_POST['_centralmidi_genero_id']) : 0;

        $artista = '';
        if ($artista_id) {
            $a = CentralMidi_DB::get_artista($artista_id);
            if ($a) {
                $artista = $a->nome;
            }
        }
        // Fallback for legacy products: keep a manual name if no select value.
        if (!$artista && isset($_POST['_centralmidi_artista'])) {
            $artista = sanitize_text_field(wp_unslash($_POST['_centralmidi_artista']));
        }
        if ($artista && !$artista_id) {
            $artista_id = CentralMidi_DB::add_artista($artista);
        }

        $genero = '';
        if ($genero_id) {
            $g = CentralMidi_DB::get_genero($genero_id);
            if ($g) {
                $genero = $g->nome;
            }
        }
        if (!$genero && isset($_POST['_centralmidi_genero'])) {
            $genero = sanitize_text_field(wp_unslash($_POST['_centralmidi_genero']));
        }
        if ($genero && !$genero_id) {
            $genero_id = CentralMidi_DB::add_genero($genero);
        }

        $mes        = isset($_POST['_centralmidi_mes_lancamento']) ? absint($_POST['_centralmidi_mes_lancamento']) : 0;
        $ano        = isset($_POST['_centralmidi_ano_lancamento']) ? absint($_POST['_centralmidi_ano_lancamento']) : (int) date('Y');
        $class      = isset($_POST['_centralmidi_classificacao']) ? CentralMidi_DB::sanitize_classificacao(sanitize_text_field(wp_unslash($_POST['_centralmidi_classificacao']))) : 'M';
        $demo_audio = isset($_POST['_centralmidi_demo_audio']) ? esc_url_raw(wp_unslash($_POST['_centralmidi_demo_audio'])) : '';

        update_post_meta($post_id, '_centralmidi_artista_id', $artista_id);
        update_post_meta($post_id, '_centralmidi_artista', $artista);
        update_post_meta($post_id, '_centralmidi_genero_id', $genero_id);
        update_post_meta($post_id, '_centralmidi_genero', $genero);
        update_post_meta($post_id, '_centralmidi_mes_lancamento', $mes);
        update_post_meta($post_id, '_centralmidi_ano_lancamento', $ano);
        update_post_meta($post_id, '_centralmidi_classificacao', $class);
        update_post_meta($post_id, '_centralmidi_demo_audio', $demo_audio);

        CentralMidi_DB::upsert($post_id, array(
            'artista_id'     => $artista_id,
            'genero_id'      => $genero_id,
            'mes_lancamento' => $mes,
            'ano_lancamento' => $ano,
            'classificacao'  => $class,
        ));

        CentralMidi_DB::clear_home_cache();
    }
}