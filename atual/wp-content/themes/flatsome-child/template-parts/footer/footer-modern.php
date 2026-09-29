<?php
/**
 * Footer Moderno Central MIDI (Child Theme)
 * 
 * Substitui o footer legado do Flatsome:
 * - Sem imagens pesadas desnecessárias
 * - Sem ícones obsoletos de pagamento (cash on delivery, etc.)
 * - Badges oficiais e leves de PIX e Cartões via Mercado Pago / PagSeguro
 * - Design escuro moderno com tipografia nítida e botões de contato
 */

if (!defined('ABSPATH')) {
    exit;
}

$catalog_url  = home_url('/midis/');
$artistas_url = home_url('/artistas/');
$servicos_url = home_url('/servicos/');
$contato_url  = home_url('/#contato');
$cart_url     = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/carrinho/');
$account_url  = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/minha-conta/');
?>
<div class="cm-modern-footer">
    <div class="cm-footer-container">
        <div class="cm-footer-grid">

            <!-- Coluna 1: Marca & Sobre -->
            <div class="cm-footer-col cm-footer-about">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="cm-footer-logo-link" title="Central MIDI - Início">
                    <img src="<?php echo esc_url(content_url('/uploads/2020/05/CentralMIDIlogo-2.png')); ?>" 
                         alt="Central MIDI" 
                         class="cm-footer-logo-img" 
                         width="180" 
                         height="46">
                </a>
                <p class="cm-footer-bio">
                    A maior e mais completa plataforma de arquivos MIDI e Playbacks profissionais do Brasil. Qualidade de estúdio, sincronização precisa de letra e melodia.
                </p>
                <div class="cm-footer-security">
                    <span class="cm-sec-badge"><i class="ri-shield-check-fill"></i> Compra 100% Segura</span>
                    <span class="cm-sec-badge"><i class="ri-download-cloud-2-fill"></i> Download Imediato</span>
                </div>
            </div>

            <!-- Coluna 2: Navegação -->
            <div class="cm-footer-col cm-footer-links">
                <h4 class="cm-footer-title"><i class="ri-compass-3-line"></i> Navegação</h4>
                <ul class="cm-footer-menu">
                    <li><a href="<?php echo esc_url(home_url('/')); ?>"><i class="ri-arrow-right-s-line"></i> Início</a></li>
                    <li><a href="<?php echo esc_url($catalog_url); ?>"><i class="ri-arrow-right-s-line"></i> Catálogo de MIDIs</a></li>
                    <li><a href="<?php echo esc_url($artistas_url); ?>"><i class="ri-arrow-right-s-line"></i> MIDIs por Artista</a></li>
                    <li><a href="<?php echo esc_url($servicos_url); ?>"><i class="ri-arrow-right-s-line"></i> Nossos Serviços</a></li>
                    <li><a href="<?php echo esc_url($account_url); ?>"><i class="ri-arrow-right-s-line"></i> Minha Conta / Pedidos</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Formas de Pagamento Seguras -->
            <div class="cm-footer-col cm-footer-payments">
                <h4 class="cm-footer-title"><i class="ri-bank-card-line"></i> Pagamento Seguro</h4>
                <p class="cm-footer-subtext">Aceitamos as principais formas de pagamento com liberação rápida:</p>
                <div class="cm-payment-badges">
                    <div class="cm-pay-pill cm-pay-pix" title="Pagamento instantâneo via PIX">
                        <i class="ri-qr-code-line"></i> <strong>PIX</strong>
                    </div>
                    <div class="cm-pay-pill cm-pay-card" title="Cartões de Crédito">
                        <i class="ri-bank-card-fill"></i> Cartões de Crédito
                    </div>
                    <div class="cm-pay-pill cm-pay-mp" title="Mercado Pago / Checkout Seguro">
                        <i class="ri-lock-2-line"></i> Checkout Criptografado
                    </div>
                </div>
                <div class="cm-pix-keys">
                    <span class="cm-pix-label">Chave PIX Oficial:</span>
                    <code class="cm-pix-code">contato@centralmidi.com.br</code>
                </div>
            </div>

            <!-- Coluna 4: Atendimento & Suporte -->
            <div class="cm-footer-col cm-footer-support">
                <h4 class="cm-footer-title"><i class="ri-customer-service-2-line"></i> Atendimento</h4>
                <p class="cm-footer-subtext">Precisa de suporte com seu pedido ou encomenda personalizada?</p>
                <div class="cm-support-buttons">
                    <a href="https://wa.me/5531984511174?text=Olá!%20Estou%20no%20site%20da%20Central%20MIDI%20e%20gostaria%20de%20ajuda." target="_blank" rel="noopener noreferrer" class="cm-btn-footer-wa">
                        <i class="ri-whatsapp-fill"></i> (31) 98451-1174
                    </a>
                    <a href="mailto:contato@centralmidi.com.br" class="cm-btn-footer-email">
                        <i class="ri-mail-send-line"></i> contato@centralmidi.com.br
                    </a>
                </div>
                <span class="cm-support-hours"><i class="ri-time-line"></i> Seg a Sex: 09h às 18h | Sáb: 09h às 13h</span>
            </div>

        </div>
    </div>

    <!-- Barra Inferior de Direitos Autorais -->
    <div class="cm-footer-bottom">
        <div class="cm-footer-container cm-bottom-content">
            <div class="cm-copyright">
                &copy; <?php echo date('Y'); ?> <strong>Central MIDI</strong>. Todos os direitos reservados.
            </div>
            <div class="cm-disclaimer">
                Arquivos destinados ao estudo, aprendizado e uso profissional em conformidade com as leis vigentes.
            </div>
        </div>
    </div>
</div>
