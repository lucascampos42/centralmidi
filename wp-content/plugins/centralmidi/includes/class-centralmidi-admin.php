<?php
/**
 * Central MIDI Admin: metabox on WooCommerce products + CRUD of reference lists.
 *
 * Fields: artista, genero, categoria, mes_lancamento, classificacao, demo_audio.
 */

defined('ABSPATH') || exit;

class CentralMidi_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post_product', array($this, 'save_meta_box'));
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

        add_submenu_page(
            'centralmidi',
            __('Categorias', 'centralmidi'),
            __('Categorias', 'centralmidi'),
            'manage_options',
            'centralmidi-categorias',
            array($this, 'render_categorias_page')
        );
    }

    /**
     * Config of a reference list (artista | genero | categoria).
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
            'categoria' => array(
                'page'     => 'centralmidi-categorias',
                'title'    => __('Central MIDI — Categorias', 'centralmidi'),
                'desc'     => __('Cadastre aqui as categorias de produto (ex: Instrumental, Playback, Religioso...). Elas aparecem como select na edição dos produtos MIDI.', 'centralmidi'),
                'add_text' => __('Adicionar Categoria', 'centralmidi'),
                'edit_text' => __('Editar Categoria', 'centralmidi'),
                'list_title' => __('Categorias Cadastradas', 'centralmidi'),
                'placeholder' => __('Nome da categoria', 'centralmidi'),
                'empty'    => __('Nenhuma categoria cadastrada.', 'centralmidi'),
                'confirm'  => __('Excluir esta categoria?', 'centralmidi'),
                'added'    => __('Categoria adicionada com sucesso.', 'centralmidi'),
                'updated'  => __('Categoria atualizada com sucesso.', 'centralmidi'),
                'updated_err' => __('Não foi possível atualizar (nome duplicado ou vazio).', 'centralmidi'),
                'removed'  => __('Categoria removida.', 'centralmidi'),
                'empty_err' => __('Informe o nome da categoria.', 'centralmidi'),
                'prefix'   => 'categoria',
                'get_all'  => 'get_categorias',
                'get_one'  => 'get_categoria',
                'add'      => 'add_categoria',
                'update'   => 'update_categoria',
                'delete'   => 'delete_categoria',
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
                $id = CentralMidi_DB::{$cfg['add']}($nome);
                $this->set_notice($id ? 'success' : 'error', $id ? $cfg['added'] : $cfg['empty_err']);
            } else {
                $this->set_notice('error', $cfg['empty_err']);
            }
        }

        if (isset($_POST['centralmidi_update_' . $prefix]) && check_admin_referer($nonce)) {
            $id   = absint($_POST['centralmidi_' . $prefix . '_id'] ?? 0);
            $nome = sanitize_text_field(wp_unslash($_POST['centralmidi_' . $prefix . '_nome'] ?? ''));
            $ok   = CentralMidi_DB::{$cfg['update']}($id, $nome);
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
                    <p>
                        <button type="submit" name="centralmidi_update_<?php echo esc_attr($prefix); ?>" class="button button-primary"><?php esc_html_e('Salvar', 'centralmidi'); ?></button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . $cfg['page'])); ?>" class="button"><?php esc_html_e('Cancelar', 'centralmidi'); ?></a>
                    </p>
                <?php else : ?>
                    <p>
                        <input type="text" name="centralmidi_novo_<?php echo esc_attr($prefix); ?>" placeholder="<?php echo esc_attr($cfg['placeholder']); ?>" class="regular-text" style="width:100%;" />
                    </p>
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

    public function render_artistas_page() {
        $this->render_referencia_page('artista');
    }

    public function render_generos_page() {
        $this->render_referencia_page('genero');
    }

    public function render_categorias_page() {
        $this->render_referencia_page('categoria');
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
        $categoria_id   = (int) get_post_meta($post->ID, '_centralmidi_categoria_id', true);
        $categoria      = get_post_meta($post->ID, '_centralmidi_categoria', true);
        $mes_lancamento = get_post_meta($post->ID, '_centralmidi_mes_lancamento', true);
        $classificacao  = get_post_meta($post->ID, '_centralmidi_classificacao', true);
        $classificacao  = CentralMidi_DB::sanitize_classificacao($classificacao);
        $demo_audio     = get_post_meta($post->ID, '_centralmidi_demo_audio', true);

        $artistas   = CentralMidi_DB::get_artistas();
        $generos    = CentralMidi_DB::get_generos();
        $categorias = CentralMidi_DB::get_categorias();

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
                <label for="centralmidi_categoria" style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Categoria', 'centralmidi'); ?></label>
                <select id="centralmidi_categoria" name="_centralmidi_categoria_id" style="width:100%;">
                    <option value="0">— <?php esc_html_e('Selecione a categoria', 'centralmidi'); ?> —</option>
                    <?php foreach ($categorias as $c) : ?>
                        <option value="<?php echo esc_attr($c->id); ?>" <?php selected($categoria_id, $c->id); ?>><?php echo esc_html($c->nome); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php echo wp_kses_post(sprintf(
                        __('Selecione uma categoria cadastrada ou <a href="%s">gerencie a lista de categorias</a>.', 'centralmidi'),
                        esc_url(admin_url('admin.php?page=centralmidi-categorias'))
                    )); ?>
                </p>
                <input type="hidden" name="_centralmidi_categoria" value="<?php echo esc_attr($categoria); ?>" />
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
        $categoria_id = isset($_POST['_centralmidi_categoria_id']) ? absint($_POST['_centralmidi_categoria_id']) : 0;

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

        $categoria = '';
        if ($categoria_id) {
            $c = CentralMidi_DB::get_categoria($categoria_id);
            if ($c) {
                $categoria = $c->nome;
            }
        }
        if (!$categoria && isset($_POST['_centralmidi_categoria'])) {
            $categoria = sanitize_text_field(wp_unslash($_POST['_centralmidi_categoria']));
        }
        if ($categoria && !$categoria_id) {
            $categoria_id = CentralMidi_DB::add_categoria($categoria);
        }

        $mes        = isset($_POST['_centralmidi_mes_lancamento']) ? absint($_POST['_centralmidi_mes_lancamento']) : 0;
        $class      = isset($_POST['_centralmidi_classificacao']) ? CentralMidi_DB::sanitize_classificacao(sanitize_text_field(wp_unslash($_POST['_centralmidi_classificacao']))) : 'M';
        $demo_audio = isset($_POST['_centralmidi_demo_audio']) ? esc_url_raw(wp_unslash($_POST['_centralmidi_demo_audio'])) : '';

        update_post_meta($post_id, '_centralmidi_artista_id', $artista_id);
        update_post_meta($post_id, '_centralmidi_artista', $artista);
        update_post_meta($post_id, '_centralmidi_genero_id', $genero_id);
        update_post_meta($post_id, '_centralmidi_genero', $genero);
        update_post_meta($post_id, '_centralmidi_categoria_id', $categoria_id);
        update_post_meta($post_id, '_centralmidi_categoria', $categoria);
        update_post_meta($post_id, '_centralmidi_mes_lancamento', $mes);
        update_post_meta($post_id, '_centralmidi_classificacao', $class);
        update_post_meta($post_id, '_centralmidi_demo_audio', $demo_audio);

        CentralMidi_DB::upsert($post_id, array(
            'artista_id'     => $artista_id,
            'genero_id'      => $genero_id,
            'categoria_id'   => $categoria_id,
            'mes_lancamento' => $mes,
            'classificacao'  => $class,
        ));

        CentralMidi_DB::clear_home_cache();
    }
}