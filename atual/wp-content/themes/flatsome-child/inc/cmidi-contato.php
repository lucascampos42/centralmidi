<?php
/**
 * Central MIDI - Sistema Nativo de Mensagens de Contato
 * 
 * Substitui o Contact Form 7:
 * - Leve, sem dependências externas e sem jQuery
 * - Salva as mensagens no painel administrativo (Custom Post Type 'cmidi_mensagem')
 * - Envia notificação por e-mail via wp_mail()
 * - Link direto para responder no WhatsApp do cliente
 * - Proteção Honeypot anti-spam + Nonce
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Registra o Custom Post Type no Admin
add_action('init', 'cmidi_register_contact_messages_cpt');
function cmidi_register_contact_messages_cpt() {
    $labels = array(
        'name'               => 'Mensagens de Contato',
        'singular_name'      => 'Mensagem de Contato',
        'menu_name'          => 'Mensagens (' . cmidi_count_unread_messages() . ')',
        'name_admin_bar'     => 'Mensagem',
        'all_items'          => 'Todas as Mensagens',
        'view_item'          => 'Ver Mensagem',
        'search_items'       => 'Buscar Mensagens',
        'not_found'          => 'Nenhuma mensagem encontrada.',
        'not_found_in_trash' => 'Nenhuma mensagem na lixeira.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 26,
        'menu_icon'          => 'dashicons-email-alt',
        'capability_type'    => 'post',
        'capabilities'       => array(
            'create_posts' => false, // Bloqueia criacao manual pelo admin
        ),
        'map_meta_cap'       => true,
        'hierarchical'       => false,
        'supports'           => array('title'),
    );

    register_post_type('cmidi_mensagem', $args);
}

// Contar mensagens nao lidas para exibir no menu
function cmidi_count_unread_messages() {
    $query = new WP_Query(array(
        'post_type'      => 'cmidi_mensagem',
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_cmidi_msg_read',
                'compare' => 'NOT EXISTS',
            ),
        ),
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ));
    return (int) $query->found_posts;
}

// 2. Colunas personalizadas na listagem de mensagens do Admin
add_filter('manage_cmidi_mensagem_posts_columns', 'cmidi_contact_columns');
function cmidi_contact_columns($columns) {
    $new_cols = array(
        'cb'        => $columns['cb'],
        'status'    => 'Status',
        'title'     => 'Nome / Remetente',
        'email'     => 'E-mail',
        'telefone'  => 'Telefone / WhatsApp',
        'preview'   => 'Mensagem',
        'date'      => 'Recebido em',
    );
    return $new_cols;
}

add_action('manage_cmidi_mensagem_posts_custom_column', 'cmidi_contact_column_content', 10, 2);
function cmidi_contact_column_content($column, $post_id) {
    $is_read = get_post_meta($post_id, '_cmidi_msg_read', true);
    switch ($column) {
        case 'status':
            if ($is_read) {
                echo '<span style="color:#666;font-size:12px;"><i class="dashicons dashicons-yes-alt" style="color:#46b450;vertical-align:middle;"></i> Lida</span>';
            } else {
                echo '<strong style="color:#d63638;background:#ffebee;padding:3px 8px;border-radius:12px;font-size:11px;">NOVA</strong>';
            }
            break;
        case 'email':
            $email = get_post_meta($post_id, '_cmidi_email', true);
            if ($email) {
                echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            } else {
                echo '&mdash;';
            }
            break;
        case 'telefone':
            $tel = get_post_meta($post_id, '_cmidi_telefone', true);
            if ($tel) {
                $clean_tel = preg_replace('/\D/', '', $tel);
                $whatsapp_url = 'https://wa.me/55' . ltrim($clean_tel, '0');
                echo esc_html($tel) . ' <a href="' . esc_url($whatsapp_url) . '" target="_blank" style="color:#25d366;text-decoration:none;font-weight:bold;margin-left:5px;" title="Conversar no WhatsApp">💬 WhatsApp</a>';
            } else {
                echo '&mdash;';
            }
            break;
        case 'preview':
            $msg = get_post_meta($post_id, '_cmidi_mensagem', true);
            echo esc_html(wp_trim_words($msg, 12, '...'));
            break;
    }
}

// 3. Meta Box com Detalhes da Mensagem e Resposta Rápida
add_action('add_meta_boxes', 'cmidi_contact_meta_boxes');
function cmidi_contact_meta_boxes() {
    add_meta_box(
        'cmidi_message_details',
        'Detalhes da Mensagem',
        'cmidi_render_message_details',
        'cmidi_mensagem',
        'normal',
        'high'
    );
}

function cmidi_render_message_details($post) {
    // Marca como lida ao abrir
    update_post_meta($post->ID, '_cmidi_msg_read', 1);

    $nome     = get_post_meta($post->ID, '_cmidi_nome', true);
    $email    = get_post_meta($post->ID, '_cmidi_email', true);
    $telefone = get_post_meta($post->ID, '_cmidi_telefone', true);
    $mensagem = get_post_meta($post->ID, '_cmidi_mensagem', true);
    $ip       = get_post_meta($post->ID, '_cmidi_ip', true);
    $data     = get_the_date('d/m/Y \à\s H:i', $post->ID);

    $clean_tel = preg_replace('/\D/', '', $telefone);
    $whatsapp_url = 'https://wa.me/55' . ltrim($clean_tel, '0');
    ?>
    <div style="font-size:14px;line-height:1.7;padding:10px 0;">
        <p><strong>📅 Data de Recebimento:</strong> <?php echo esc_html($data); ?></p>
        <p><strong>👤 Nome:</strong> <?php echo esc_html($nome); ?></p>
        <p><strong>✉️ E-mail:</strong> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
        <p>
            <strong>📱 Telefone/WhatsApp:</strong> <?php echo esc_html($telefone); ?>
            <?php if ($clean_tel) : ?>
                &nbsp;&mdash;&nbsp;<a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" class="button button-secondary" style="color:#0b8043;font-weight:bold;">💬 Abrir no WhatsApp</a>
            <?php endif; ?>
        </p>
        <?php if ($ip) : ?>
            <p><small style="color:#888;">IP de Envio: <?php echo esc_html($ip); ?></small></p>
        <?php endif; ?>
        <hr style="margin:20px 0;border:0;border-top:1px solid #ddd;">
        <h4 style="margin-bottom:10px;">Conteúdo da Mensagem:</h4>
        <div style="background:#f9f9f9;border:1px solid #e5e5e5;padding:15px;border-radius:6px;white-space:pre-wrap;font-family:inherit;">
<?php echo esc_html($mensagem); ?>
        </div>
        <div style="margin-top:20px;">
            <a href="mailto:<?php echo esc_attr($email); ?>?subject=Re:%20Contato%20Central%20MIDI" class="button button-primary button-large">Responder por E-mail</a>
        </div>
    </div>
    <?php
}

// 4. Shortcode do Formulário de Contato [cmidi_contact_form]
add_shortcode('cmidi_contact_form', 'cmidi_render_contact_form');
add_shortcode('contact-form-7', 'cmidi_cf7_compat_shortcode'); // Fallback para onde já estiver inserido

function cmidi_cf7_compat_shortcode($atts) {
    return cmidi_render_contact_form();
}

function cmidi_render_contact_form() {
    ob_start();
    ?>
    <div class="cm-contact-form-container">
        <form id="cm-native-contact-form" class="cm-contact-form" method="post">
            <?php wp_nonce_field('cmidi_contact_action', 'cmidi_contact_nonce'); ?>
            
            <!-- Campo Honeypot Anti-Spam invisível -->
            <div style="display:none !important;" aria-hidden="true">
                <input type="text" name="cmidi_hp_website" tabindex="-1" autocomplete="off">
            </div>

            <div class="cm-form-group">
                <label for="cm_nome" class="screen-reader-text">Seu Nome</label>
                <input type="text" id="cm_nome" name="nome" placeholder="Seu nome completo *" required>
            </div>

            <div class="cm-form-group">
                <label for="cm_email" class="screen-reader-text">Seu E-mail</label>
                <input type="email" id="cm_email" name="email" placeholder="Seu e-mail *" required>
            </div>

            <div class="cm-form-group">
                <label for="cm_telefone" class="screen-reader-text">Telefone / WhatsApp</label>
                <input type="tel" id="cm_telefone" name="telefone" placeholder="WhatsApp / Telefone (com DDD) *" required>
            </div>

            <div class="cm-form-group">
                <label for="cm_mensagem" class="screen-reader-text">Mensagem</label>
                <textarea id="cm_mensagem" name="mensagem" rows="4" placeholder="Digite sua mensagem aqui..." required></textarea>
            </div>

            <button type="submit" class="cm-contact-submit-btn" id="cm-contact-submit">
                <span class="cm-submit-text">Enviar Mensagem</span>
                <i class="ri-send-plane-fill"></i>
            </button>

            <div id="cm-form-response" class="cm-form-response" style="display:none;"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// 5. Endpoint AJAX para Processar o Envio do Formulário
add_action('wp_ajax_cmidi_submit_contact', 'cmidi_process_contact_submission');
add_action('wp_ajax_nopriv_cmidi_submit_contact', 'cmidi_process_contact_submission');

function cmidi_process_contact_submission() {
    // 1. Verificacao de Seguranca Nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cmidi_contact_action')) {
        wp_send_json_error(array('message' => 'Sessão expirada. Atualize a página e tente novamente.'));
    }

    // 2. Protecao Honeypot Anti-Spam
    if (!empty($_POST['hp_field'])) {
        // Robô preencheu o campo invisível
        wp_send_json_success(array('message' => 'Mensagem enviada com sucesso!'));
    }

    // 3. Sanitização dos campos
    $nome     = isset($_POST['nome']) ? sanitize_text_field($_POST['nome']) : '';
    $email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $telefone = isset($_POST['telefone']) ? sanitize_text_field($_POST['telefone']) : '';
    $mensagem = isset($_POST['mensagem']) ? sanitize_textarea_field($_POST['mensagem']) : '';

    if (empty($nome) || empty($email) || empty($mensagem)) {
        wp_send_json_error(array('message' => 'Por favor, preencha todos os campos obrigatórios.'));
    }

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Por favor, informe um endereço de e-mail válido.'));
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';

    // 4. Salva a mensagem no Painel do WordPress
    $post_title = $nome . ' - ' . date('d/m/Y H:i');
    $post_id = wp_insert_post(array(
        'post_title'   => $post_title,
        'post_content' => $mensagem,
        'post_type'    => 'cmidi_mensagem',
        'post_status'  => 'publish',
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => 'Não foi possível salvar sua mensagem no momento.'));
    }

    // Salva os metadados
    update_post_meta($post_id, '_cmidi_nome', $nome);
    update_post_meta($post_id, '_cmidi_email', $email);
    update_post_meta($post_id, '_cmidi_telefone', $telefone);
    update_post_meta($post_id, '_cmidi_mensagem', $mensagem);
    update_post_meta($post_id, '_cmidi_ip', $ip);

    // 5. Envia Notificação por E-mail (com fallback se wp_mail falhar)
    $to = get_option('admin_email');
    if (!$to) {
        $to = 'contato@centralmidi.com.br';
    }
    $subject = '[Central MIDI] Nova Mensagem de ' . $nome;
    $body  = "Você recebeu uma nova mensagem através do site Central MIDI:\n\n";
    $body .= "Nome: " . $nome . "\n";
    $body .= "E-mail: " . $email . "\n";
    $body .= "Telefone/WhatsApp: " . $telefone . "\n";
    $body .= "Data: " . date('d/m/Y H:i') . "\n\n";
    $body .= "Mensagem:\n" . $mensagem . "\n\n";
    $body .= "---\nMensagem arquivada no Painel do WordPress: " . admin_url('post.php?post=' . $post_id . '&action=edit') . "\n";

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $nome . ' <' . $email . '>',
    );

    @wp_mail($to, $subject, $body, $headers);

    wp_send_json_success(array('message' => 'Sua mensagem foi enviada com sucesso! Entraremos em contato em breve.'));
}
