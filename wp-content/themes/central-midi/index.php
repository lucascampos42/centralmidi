<?php
/**
 * Homepage Template - Central Midi
 * Shows:
 * 1. Current Month Releases (up to 30)
 * 2. Previous Month Releases (up to 30)
 * 3. 2 Months Ago Releases (up to 30)
 */
get_header();

get_template_part('template-parts/carousel');

// Determine 3 latest months from DB or calendar
$cm_db_available = class_exists('CentralMidi_DB');
$db_months = $cm_db_available ? CentralMidi_DB::distinct('mes_lancamento') : array();
rsort($db_months);

$current_calendar_month = (int) date('n');
$m1 = !empty($db_months[0]) ? (int)$db_months[0] : $current_calendar_month;
$m2 = !empty($db_months[1]) ? (int)$db_months[1] : (($current_calendar_month - 1 < 1) ? 12 : $current_calendar_month - 1);
$m3 = !empty($db_months[2]) ? (int)$db_months[2] : (($current_calendar_month - 2 < 1) ? (12 + ($current_calendar_month - 2)) : $current_calendar_month - 2);

$featured_months = array(
    array(
        'mes'      => $m1,
        'badge'    => 'Lançamentos do Mês',
        'icon'     => 'ri-fire-fill',
        'subtitle' => 'Músicas recém-adicionadas e novidades para o seu repertório.',
    ),
    array(
        'mes'      => $m2,
        'badge'    => 'Lançamentos do Mês Anterior',
        'icon'     => 'ri-sparkling-2-fill',
        'subtitle' => 'Sucessos lançados no mês passado disponíveis para download.',
    ),
    array(
        'mes'      => $m3,
        'badge'    => 'Lançamentos Recentes',
        'icon'     => 'ri-box-3-fill',
        'subtitle' => 'Confira também as faixas lançadas há 2 meses.',
    ),
);

// Cache monthly release data (6h) so the homepage is stable and fast.
$cache_key = 'centralmidi_home_' . implode('_', array($m1, $m2, $m3));
$month_data = get_transient($cache_key);

if (false === $month_data) {
    $month_data = array();
    foreach ($featured_months as $month_config) {
        $mes_num = $month_config['mes'];
        $total_mes = $cm_db_available ? CentralMidi_DB::count_by_month($mes_num) : 0;
        $product_ids = $cm_db_available ? CentralMidi_DB::get_midis_by_month($mes_num, 30) : array();

        $month_data[] = array(
            'mes'          => $mes_num,
            'mes_nome'     => $cm_db_available ? CentralMidi_DB::mes_nome($mes_num) : '',
            'total'        => $total_mes,
            'product_ids'  => $product_ids,
            'badge'        => $month_config['badge'],
            'icon'         => $month_config['icon'],
            'subtitle'     => $month_config['subtitle'],
        );
    }
    set_transient($cache_key, $month_data, 6 * HOUR_IN_SECONDS);
}
?>

<div class="cm-container cm-home-releases">
    <?php foreach ($month_data as $index => $release) : ?>
        <section class="cm-month-section<?php echo ($index > 0) ? ' cm-month-section--bordered' : ''; ?>">
            <div class="cm-section-header">
                <div>
                    <span class="cm-badge"><i class="<?php echo esc_attr($release['icon']); ?>"></i> <?php echo esc_html($release['badge']); ?></span>
                    <h2 class="cm-section-title">Lançamentos de <?php echo esc_html($release['mes_nome']); ?></h2>
                    <p class="cm-section-subtitle"><?php echo esc_html($release['subtitle']); ?></p>
                </div>

                <div>
                    <a href="<?php echo esc_url(add_query_arg('mes_lancamento', $release['mes'], centralmidi_catalog_url())); ?>" class="cm-btn cm-btn-outline">
                        Ver todos de <?php echo esc_html($release['mes_nome']); ?> (<?php echo esc_html($release['total']); ?>) <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>

            <?php if (!empty($release['product_ids'])) : ?>
                <div class="cm-tracks-grid">
                    <?php foreach ($release['product_ids'] as $pid) : ?>
                        <?php get_template_part('template-parts/card-midi', null, array('product_id' => $pid)); ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="centralmidi-empty cm-empty-state">
                    <i class="ri-music-2-line"></i>
                    <p>Nenhum lançamento cadastrado para o mês de <?php echo esc_html($release['mes_nome']); ?> ainda.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<?php
// Contact details from theme options
$whatsapp = get_theme_mod('centralmidi_whatsapp', '5531984511174');
$whatsapp_url = 'https://wa.me/' . preg_replace('/\D/', '', $whatsapp) . '?text=' . rawurlencode('Oi! Estou no site da CentralMIDI e preciso de auxílio');
$email = get_theme_mod('centralmidi_email', 'contato@centralmidi.com.br');
$pix = get_theme_mod('centralmidi_pix', 'centralmidi@gmail.com');
?>

<!-- Contact Section -->
<section id="contato" class="cm-contact-section">
    <div class="cm-container">
        <div class="cm-contact-header">
            <span class="cm-badge"><i class="ri-customer-service-2-fill"></i> Atendimento &amp; Suporte</span>
            <h2 class="cm-section-title">Fale Conosco</h2>
            <p class="cm-section-subtitle">
                Tire suas dúvidas, solicite encomendas de arranjos exclusivos ou peça auxílio com seus arquivos MIDI e playbacks.
            </p>
        </div>

        <div class="cm-contact-grid">
            <!-- WhatsApp -->
            <div class="cm-contact-card cm-contact-card--whatsapp">
                <div class="cm-contact-icon-wrapper">
                    <i class="ri-whatsapp-fill"></i>
                </div>
                <h3 class="cm-contact-card-title">WhatsApp Direto</h3>
                <p class="cm-contact-card-desc">
                    Atendimento rápido de segunda a sábado para dúvidas, envio de referências e suporte aos pedidos.
                </p>
                <div class="cm-contact-pill-list" style="margin-bottom: 20px;">
                    <div class="cm-contact-pill">
                        <span><i class="ri-phone-line"></i> (31) 98451-1174</span>
                    </div>
                    <div class="cm-contact-pill">
                        <span><i class="ri-phone-line"></i> (31) 99802-3523</span>
                    </div>
                </div>
                <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="cm-btn cm-btn-primary" style="width: 100%; justify-content: center; background: #25d366; color: #000; border-color: #25d366;">
                    <i class="ri-whatsapp-line"></i> Iniciar Atendimento
                </a>
            </div>

            <!-- Email -->
            <div class="cm-contact-card cm-contact-card--email">
                <div class="cm-contact-icon-wrapper">
                    <i class="ri-mail-send-fill"></i>
                </div>
                <h3 class="cm-contact-card-title">E-mail Oficial</h3>
                <p class="cm-contact-card-desc">
                    Envie comprovantes, links de áudio ou solicite sugestões de repertório que não encontrou no catálogo.
                </p>
                <div class="cm-contact-pill-list" style="margin-bottom: 20px;">
                    <div class="cm-contact-pill">
                        <span><i class="ri-mail-line"></i> <?php echo esc_html($email); ?></span>
                    </div>
                    <div class="cm-contact-pill">
                        <span><i class="ri-mail-line"></i> centralmidi@gmail.com</span>
                    </div>
                </div>
                <a href="mailto:<?php echo esc_attr($email); ?>" class="cm-btn cm-btn-outline" style="width: 100%; justify-content: center;">
                    <i class="ri-mail-line"></i> Enviar Mensagem
                </a>
            </div>

            <!-- PIX -->
            <div class="cm-contact-card cm-contact-card--pix">
                <div class="cm-contact-icon-wrapper">
                    <i class="ri-qr-code-fill"></i>
                </div>
                <h3 class="cm-contact-card-title">Chaves PIX</h3>
                <p class="cm-contact-card-desc">
                    Pagamento instantâneo com envio prioritário dos arquivos produzidos sob encomenda.
                </p>
                <div class="cm-contact-pill-list" style="margin-bottom: 20px;">
                    <div class="cm-contact-pill">
                        <span><i class="ri-key-2-line"></i> <?php echo esc_html($pix); ?></span>
                    </div>
                    <div class="cm-contact-pill">
                        <span><i class="ri-key-2-line"></i> <?php echo esc_html($email); ?></span>
                    </div>
                </div>
                <a href="<?php echo esc_url(home_url('/servicos/')); ?>" class="cm-btn cm-btn-outline" style="width: 100%; justify-content: center;">
                    <i class="ri-information-line"></i> Ver Serviços &amp; Preços
                </a>
            </div>

            <!-- Redes Sociais -->
            <div class="cm-contact-card cm-contact-card--social">
                <div class="cm-contact-icon-wrapper">
                    <i class="ri-share-forward-fill"></i>
                </div>
                <h3 class="cm-contact-card-title">Redes Sociais</h3>
                <p class="cm-contact-card-desc">
                    Acompanhe lançamentos semanais, demonstrações em vídeo e cupons de desconto exclusivos.
                </p>
                <div class="cm-contact-social-links" style="margin-top: auto; width: 100%;">
                    <a href="https://www.instagram.com/centralmidioficial/" target="_blank" rel="noopener noreferrer" class="cm-social-btn" style="flex: 1; justify-content: center;">
                        <i class="ri-instagram-line"></i> Instagram
                    </a>
                    <a href="https://pt-br.facebook.com/centralmidioficial/" target="_blank" rel="noopener noreferrer" class="cm-social-btn" style="flex: 1; justify-content: center;">
                        <i class="ri-facebook-fill"></i> Facebook
                    </a>
                </div>
            </div>
        </div>

        <div class="cm-contact-disclaimer">
            <p>
                <i class="ri-shield-check-line"></i> <strong>Aviso Legal &amp; Copyright:</strong>
                O uso e divulgação dos arquivos é de responsabilidade do usuário para finalidades profissionais, estudo e apresentações.
                Trabalhamos com garantia de qualidade e suporte dedicado para todos os nossos clientes.
            </p>
        </div>
    </div>
</section>

<?php get_footer(); ?>