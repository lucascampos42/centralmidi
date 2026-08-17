<?php
/**
 * Template Name: Página de Serviços
 * Template Post Type: page
 */
get_header();

$whatsapp = centralmidi_option_whatsapp();
$email    = centralmidi_option_email();
$pix      = centralmidi_option_pix();
?>

<div class="cm-hero-banner cm-hero-banner--compact">
    <div class="cm-container">
        <span class="cm-badge"><i class="ri-sound-module-line"></i> Produção Musical Especializada</span>
        <h1 class="cm-hero-title">Serviços & Produções Sob Encomenda</h1>
        <p class="cm-hero-subtitle">
            Arranjos profissionais em formato MIDI multitrack, playbacks em áudio (MP3/WAV), sincronização de letras (#L / #RLM) e ritmos personalizados para teclados.
        </p>
    </div>
</div>

<div class="cm-container cm-page-wrap">
    <div class="cm-services-section">
        <div class="cm-section-heading">
            <span class="cm-badge">Nossas Soluções</span>
            <h2>O que nós produzimos</h2>
        </div>

        <div class="cm-services-grid">
            <div class="cm-service-card">
                <div class="cm-service-icon cm-service-icon--primary">
                    <i class="ri-music-2-fill"></i>
                </div>
                <h3>MIDIs com Letra & Melodia Sincronizadas</h3>
                <p>Produção completa em formato <strong>.MID (#RLM)</strong> para todos os estilos e nacionalidades (Brasileiras, Latinas, Européias, Italianas, Japonesas, etc.).</p>
            </div>

            <div class="cm-service-card">
                <div class="cm-service-icon cm-service-icon--accent">
                    <i class="ri-keyboard-line"></i>
                </div>
                <h3>Ritmos para Teclados Korg PA</h3>
                <p>Programação e criação de ritmos (*styles*) sob medida para teclados arranjadores da linha <strong>Korg PA</strong> com timbres balanceados.</p>
            </div>

            <div class="cm-service-card">
                <div class="cm-service-icon cm-service-icon--yellow">
                    <i class="ri-disc-line"></i>
                </div>
                <h3>Arranjos & Re-arranjos Exclusivos</h3>
                <p>Criação de arranjos para músicas inéditas ou regravações com instrumentação moderna, divisão de pistas e fidelidade harmônica.</p>
            </div>

            <div class="cm-service-card">
                <div class="cm-service-icon cm-service-icon--purple">
                    <i class="ri-file-text-line"></i>
                </div>
                <h3>Inserção de Melodia e Letras (#L / #M)</h3>
                <p>Sincronização de letra silábica (*Karaokê / Lyrics*) e inclusão de linha melódica guia em arquivos MIDI já existentes.</p>
            </div>

            <div class="cm-service-card">
                <div class="cm-service-icon cm-service-icon--red">
                    <i class="ri-headphone-line"></i>
                </div>
                <h3>Playbacks em Áudio (MP3 / WAV)</h3>
                <p>Renderização de playbacks com SoundFonts profissionais de alta definição, mixados e masterizados prontos para apresentações.</p>
            </div>

            <div class="cm-service-card">
                <div class="cm-service-icon cm-service-icon--teal">
                    <i class="ri-play-list-add-line"></i>
                </div>
                <h3>Pot-Pourri (Medley) Personalizado</h3>
                <p>Montagem de sequências contínuas de músicas emendadas com transição harmônica e rítmica perfeita para o seu show.</p>
            </div>
        </div>
    </div>

    <div class="cm-box cm-box--pad-lg cm-process-section">
        <div class="cm-section-heading">
            <span class="cm-badge">Passo a Passo</span>
            <h2>Como Solicitar seu MIDI ou Playback</h2>
        </div>

        <div class="cm-steps-grid">
            <div class="cm-step-item">
                <div class="cm-step-number">1</div>
                <h4>Envie o Áudio de Referência</h4>
                <p>Envie o link ou áudio da música desejada via WhatsApp ou E-mail informando os detalhes que precisa no arranjo.</p>
            </div>

            <div class="cm-step-item">
                <div class="cm-step-number">2</div>
                <h4>Avaliação & Orçamento</h4>
                <p>Nossa equipe analisa a complexidade do arranjo e retorna com o valor exato e prazo previsto de entrega.</p>
            </div>

            <div class="cm-step-item">
                <div class="cm-step-number">3</div>
                <h4>Produção com Garantia</h4>
                <p>Após a confirmação do pagamento, o arquivo é produzido, testado em equipamentos reais e enviado diretamente para você.</p>
            </div>
        </div>
    </div>

    <div class="cm-info-grid">
        <div class="cm-box cm-box--pad">
            <h3 class="cm-box-title">
                <i class="ri-shield-check-fill cm-box-title-icon"></i> Garantia & Informações
            </h3>
            <ul class="cm-info-list">
                <li>
                    <i class="ri-checkbox-circle-fill"></i>
                    <span><strong>Garantia Total:</strong> Revisamos qualquer ajuste de tonalidade, volume ou andamento necessário.</span>
                </li>
                <li>
                    <i class="ri-checkbox-circle-fill"></i>
                    <span><strong>Padrão Profissional:</strong> Arquivos compatíveis com teclados Roland, Yamaha, Korg, softwares e aplicativos.</span>
                </li>
                <li>
                    <i class="ri-checkbox-circle-fill"></i>
                    <span><strong>Backup Recomendado:</strong> Mantenha uma cópia segura dos arquivos entregues em seu computador ou nuvem.</span>
                </li>
            </ul>
        </div>

        <div class="cm-box cm-box--pad">
            <h3 class="cm-box-title">
                <i class="ri-qr-code-line cm-box-title-icon cm-box-title-icon--accent"></i> Formas de Pagamento
            </h3>
            <p class="cm-box-text">
                Aceitamos pagamentos rápidos e seguros com liberação imediata via <strong>PIX</strong> e opções de cartões.
            </p>
            <div class="cm-pix-box">
                <span><i class="ri-key-2-line"></i> Chave PIX (E-mail)</span>
                <strong><?php echo esc_html($pix); ?></strong>
            </div>
        </div>
    </div>

    <div class="cm-cta-box">
        <h2>Precisa de um MIDI ou Playback Exclusivo?</h2>
        <p class="cm-cta-text">
            Fale agora mesmo com nossa equipe pelo WhatsApp. Avaliamos sua música e passamos o orçamento sem compromisso.
        </p>
        <div class="cm-cta-actions">
            <a href="https://wa.me/<?php echo esc_attr($whatsapp); ?>?text=<?php echo rawurlencode('Oi! Gostaria de solicitar um orçamento de serviço no CentralMIDI'); ?>" target="_blank" rel="noopener" class="cm-btn cm-btn-whatsapp">
                <i class="ri-whatsapp-line"></i> Solicitar Orçamento no WhatsApp
            </a>
            <a href="mailto:<?php echo esc_attr($email); ?>?subject=<?php echo rawurlencode('Orçamento de Serviço - Central MIDI'); ?>" class="cm-btn cm-btn-outline cm-cta-email">
                <i class="ri-mail-send-line"></i> Enviar por E-mail
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>