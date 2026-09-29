<?php
/**
 * Header Wrapper Customizado Central MIDI
 *
 * Desacoplado do framework de layout do Flatsome:
 * - Topbar elegante e moderno (WhatsApp, Redes Sociais, Newsletter com formulário nativo)
 * - Header Principal com Logo, Links diretos com ícones, Busca com dropdown / AJAX, Conta, Carrinho e Theme Switcher
 * - Menu Mobile em TELA CHEIA (Fullscreen Overlay), moderno e isolado do tema
 *
 * @package CentralMidi_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_url = home_url('/');
$is_logged_in = is_user_logged_in();
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
$cart_count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
$cart_total = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_total() : 'R$ 0,00';

$logo_src = get_stylesheet_directory_uri() . '/assets/images/placeholder.png';
$logo_id = get_theme_mod('site_logo');
if ($logo_id && function_exists('wp_get_attachment_image_src')) {
    $logo_img = wp_get_attachment_image_src($logo_id, 'full');
    if ($logo_img) {
        $logo_src = $logo_img[0];
    }
} else {
    $logo_src = home_url('/wp-content/uploads/2020/05/CentralMIDIlogo-2.png');
}
?>

<!-- Top Bar Superior -->
<div id="cm-top-bar" class="cm-top-bar">
    <div class="cm-top-bar-container">
        <!-- Esquerda / Frase de boas-vindas ou status -->
        <div class="cm-top-bar-left">
            <span class="cm-top-badge"><i class="ri-music-fill"></i> Central MIDI</span>
            <span class="cm-top-tagline">Seu mundo musical digital na Net!</span>
        </div>

        <!-- Direita / WhatsApp, Redes Sociais, Newsletter -->
        <div class="cm-top-bar-right">
            <a href="https://wa.me/5531984511174?text=Oi!%20Estou%20no%20site%20da%20CentralMIDI%20e%20preciso%20de%20aux%C3%ADlio" target="_blank" rel="noopener noreferrer" class="cm-top-link cm-top-whatsapp" title="Fale no WhatsApp">
                <i class="ri-whatsapp-fill"></i>
                <span class="cm-hide-on-mobile">(31) 98451-1174</span>
            </a>

            <div class="cm-top-socials">
                <a href="https://pt-br.facebook.com/centralmidioficial/" target="_blank" rel="noopener nofollow" class="cm-social-icon cm-facebook" title="Facebook" aria-label="Facebook">
                    <i class="ri-facebook-fill"></i>
                </a>
                <a href="https://www.instagram.com/centralmidioficial/" target="_blank" rel="noopener nofollow" class="cm-social-icon cm-instagram" title="Instagram" aria-label="Instagram">
                    <i class="ri-instagram-line"></i>
                </a>
                <a href="https://twitter.com/CentralMidi" target="_blank" rel="noopener nofollow" class="cm-social-icon cm-twitter" title="Twitter / X" aria-label="Twitter">
                    <i class="ri-twitter-x-line"></i>
                </a>
            </div>

            <button type="button" class="cm-top-newsletter-btn" id="cm-open-newsletter-btn" aria-label="Newsletter">
                <i class="ri-mail-send-line"></i>
                <span class="cm-hide-on-mobile">Newsletter</span>
            </button>
        </div>
    </div>
</div>

<!-- Header Principal Sticky Wrapper -->
<div id="cm-main-header-wrapper" class="cm-main-header-wrapper">
    <div id="cm-main-header" class="cm-main-header">
    <div class="cm-header-container">
        
        <!-- Logo Central MIDI -->
        <div class="cm-header-logo">
            <a href="<?php echo esc_url($site_url); ?>" title="Central MIDI - Início" rel="home">
                <img src="<?php echo esc_url($logo_src); ?>" alt="Central MIDI" class="cm-logo-img">
            </a>
        </div>

        <!-- Navegação Desktop -->
        <nav class="cm-desktop-nav" aria-label="Navegação Principal">
            <ul class="cm-nav-list">
                <li class="cm-nav-item <?php echo is_front_page() ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url($site_url); ?>" class="cm-nav-link">Home</a>
                </li>
                <li class="cm-nav-item <?php echo is_page('servicos') ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url(home_url('/servicos/')); ?>" class="cm-nav-link">Serviços</a>
                </li>
                
                <!-- Dropdown Midis -->
                <li class="cm-nav-item cm-has-dropdown <?php echo (is_page(array('midis', 'artistas', 'midis-por-genero', 'midis-por-mes-de-lancamento'))) ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url(home_url('/midis/')); ?>" class="cm-nav-link cm-dropdown-toggle">
                        <span>Midis</span>
                        <i class="ri-arrow-down-s-line cm-arrow-icon"></i>
                    </a>
                    <ul class="cm-dropdown-menu">
                        <li>
                            <a href="<?php echo esc_url(home_url('/midis/')); ?>">
                                <div class="cm-menu-icon" style="background: rgba(0, 210, 132, 0.15); color: #00d284;">
                                    <i class="ri-folder-music-line"></i>
                                </div>
                                <div class="cm-menu-text">
                                    <strong>Todos os Midis</strong>
                                    <span>Catálogo completo e lançamentos</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/artistas/')); ?>">
                                <div class="cm-menu-icon" style="background: rgba(0, 210, 132, 0.15); color: #00d284;">
                                    <i class="ri-user-star-line"></i>
                                </div>
                                <div class="cm-menu-text">
                                    <strong>Por Artista</strong>
                                    <span>Navegue por letras de A a Z</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/midis-por-genero/')); ?>">
                                <div class="cm-menu-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                                    <i class="ri-music-2-line"></i>
                                </div>
                                <div class="cm-menu-text">
                                    <strong>Por Gênero</strong>
                                    <span>Sertanejo, Pop, Rock, Forró e mais</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/midis-por-mes-de-lancamento/')); ?>">
                                <div class="cm-menu-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                                    <i class="ri-calendar-event-line"></i>
                                </div>
                                <div class="cm-menu-text">
                                    <strong>Por Mês de Lançamento</strong>
                                    <span>Últimas novidades por mês e ano</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="cm-nav-item">
                    <a href="<?php echo esc_url(home_url('/#contato')); ?>" class="cm-nav-link">Contato</a>
                </li>
            </ul>
        </nav>

        <!-- Ações do Header (Tema, Busca, Conta, Carrinho, Hambúrguer Mobile) -->
        <div class="cm-header-actions">
            <!-- Alternador de Tema Escuro / Claro -->
            <button type="button" id="cmidi-theme-toggle-btn" class="cm-theme-toggle-btn" aria-label="Alternar modo claro e escuro" title="Alternar tema">
                <i class="ri-moon-line cm-theme-icon-dark"></i>
                <i class="ri-sun-line cm-theme-icon-light"></i>
            </button>

            <!-- Botão de Busca Rápida -->
            <button type="button" class="cm-action-btn cm-search-trigger-btn" id="cm-header-search-btn" aria-label="Abrir pesquisa" title="Pesquisar Midis">
                <i class="ri-search-2-line"></i>
            </button>

            <!-- Conta / Login -->
            <a href="<?php echo esc_url($account_url); ?>" class="cm-action-btn cm-account-btn hide-for-small hide-for-medium" title="<?php echo $is_logged_in ? 'Minha Conta' : 'Entrar / Cadastre-se'; ?>" aria-label="Minha Conta">
                <i class="ri-user-3-line"></i>
                <span class="cm-account-label hide-for-medium"><?php echo $is_logged_in ? 'Conta' : 'Entrar'; ?></span>
            </a>

            <!-- Carrinho com Badge e Preço -->
            <a href="<?php echo esc_url($cart_url); ?>" class="cm-action-btn cm-cart-btn" title="Ver Carrinho" aria-label="Carrinho">
                <div class="cm-cart-icon-wrap">
                    <i class="ri-shopping-cart-2-line"></i>
                    <span class="cm-cart-badge" id="cm-cart-contents-count"><?php echo esc_html($cart_count); ?></span>
                </div>
                <span class="cm-cart-amount hide-for-medium" id="cm-cart-contents-total"><?php echo wp_kses_post($cart_total); ?></span>
            </a>

            <!-- Botão Hambúrguer Mobile -->
            <button type="button" class="cm-hamburger-btn show-for-medium" id="cm-mobile-menu-toggle" aria-label="Abrir menu mobile" aria-expanded="false" aria-controls="cm-mobile-fullscreen-menu">
                <span class="cm-hamburger-line"></span>
                <span class="cm-hamburger-line"></span>
                <span class="cm-hamburger-line"></span>
            </button>
        </div>

    </div>
</div>
</div><!-- /#cm-main-header-wrapper -->

<!-- =========================================================================
     MENU MOBILE EM TELA CHEIA (FULLSCREEN OVERLAY)
     Moderno, Fluido, 100% isolado do tema
     ========================================================================= -->
<div id="cm-mobile-fullscreen-menu" class="cm-mobile-fullscreen" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="cm-mobile-fullscreen-backdrop"></div>
    <div class="cm-mobile-fullscreen-container">
        
        <!-- Topo do Menu Mobile -->
        <div class="cm-mobile-header">
            <div class="cm-mobile-logo">
                <a href="<?php echo esc_url($site_url); ?>">
                    <img src="<?php echo esc_url($logo_src); ?>" alt="Central MIDI" class="cm-mobile-logo-img">
                </a>
            </div>
            
            <div class="cm-mobile-top-actions">
                <!-- Theme switch no mobile header -->
                <button type="button" class="cm-theme-toggle-btn" id="cm-mobile-theme-btn" aria-label="Alternar tema">
                    <i class="ri-moon-line cm-theme-icon-dark"></i>
                    <i class="ri-sun-line cm-theme-icon-light"></i>
                </button>

                <!-- Botão Fechar Tela Cheia -->
                <button type="button" class="cm-mobile-close-btn" id="cm-mobile-menu-close" aria-label="Fechar menu">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        </div>

        <!-- Barra de Busca Mobile Integrada -->
        <div class="cm-mobile-search-wrap">
            <form role="search" method="get" class="cm-mobile-search-form" action="<?php echo esc_url($site_url); ?>">
                <i class="ri-search-2-line cm-mobile-search-icon"></i>
                <input type="search" name="s" class="cm-mobile-search-input" placeholder="Buscar música, cantor..." autocomplete="off">
                <input type="hidden" name="post_type" value="product">
                <button type="submit" class="cm-mobile-search-submit" aria-label="Pesquisar">
                    <i class="ri-arrow-right-line"></i>
                </button>
            </form>
        </div>

        <!-- Links Principais com visual moderno em cartões/linhas -->
        <div class="cm-mobile-nav-scroll">
            <ul class="cm-mobile-nav-list">
                <li class="cm-mobile-nav-item">
                    <a href="<?php echo esc_url($site_url); ?>" class="cm-mobile-nav-link">
                        <span class="cm-mobile-link-icon"><i class="ri-home-4-line"></i></span>
                        <span class="cm-mobile-link-text">Início</span>
                        <i class="ri-arrow-right-s-line cm-mobile-chevron"></i>
                    </a>
                </li>
                
                <li class="cm-mobile-nav-item">
                    <a href="<?php echo esc_url(home_url('/servicos/')); ?>" class="cm-mobile-nav-link">
                        <span class="cm-mobile-link-icon"><i class="ri-customer-service-2-line"></i></span>
                        <span class="cm-mobile-link-text">Serviços</span>
                        <i class="ri-arrow-right-s-line cm-mobile-chevron"></i>
                    </a>
                </li>

                <!-- Midis Accordion / Expansível -->
                <li class="cm-mobile-nav-item cm-mobile-has-sub">
                    <button type="button" class="cm-mobile-nav-link cm-mobile-accordion-toggle" aria-expanded="false">
                        <span class="cm-mobile-link-icon"><i class="ri-folder-music-line"></i></span>
                        <span class="cm-mobile-link-text">Midis & Catálogo</span>
                        <i class="ri-add-line cm-mobile-plus-icon"></i>
                    </button>
                    <ul class="cm-mobile-sub-list">
                        <li>
                            <a href="<?php echo esc_url(home_url('/midis/')); ?>" class="cm-mobile-sub-link">
                                <i class="ri-apps-2-line" style="color: #00d284;"></i>
                                <span>Ver Todos os Midis</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/artistas/')); ?>" class="cm-mobile-sub-link">
                                <i class="ri-user-star-line" style="color: #00d284;"></i>
                                <span>Midis por Artista (A-Z)</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/midis-por-genero/')); ?>" class="cm-mobile-sub-link">
                                <i class="ri-music-2-line" style="color: #38bdf8;"></i>
                                <span>Midis por Gênero</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/midis-por-mes-de-lancamento/')); ?>" class="cm-mobile-sub-link">
                                <i class="ri-calendar-event-line" style="color: #f59e0b;"></i>
                                <span>Midis por Mês de Lançamento</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="cm-mobile-nav-item">
                    <a href="<?php echo esc_url(home_url('/#contato')); ?>" class="cm-mobile-nav-link">
                        <span class="cm-mobile-link-icon"><i class="ri-mail-line"></i></span>
                        <span class="cm-mobile-link-text">Fale Conosco / Contato</span>
                        <i class="ri-arrow-right-s-line cm-mobile-chevron"></i>
                    </a>
                </li>

                <li class="cm-mobile-nav-item">
                    <a href="<?php echo esc_url($account_url); ?>" class="cm-mobile-nav-link">
                        <span class="cm-mobile-link-icon"><i class="ri-user-line"></i></span>
                        <span class="cm-mobile-link-text"><?php echo $is_logged_in ? 'Meu Painel / Minha Conta' : 'Entrar / Criar Conta'; ?></span>
                        <i class="ri-arrow-right-s-line cm-mobile-chevron"></i>
                    </a>
                </li>

                <li class="cm-mobile-nav-item">
                    <a href="<?php echo esc_url($cart_url); ?>" class="cm-mobile-nav-link">
                        <span class="cm-mobile-link-icon"><i class="ri-shopping-cart-line"></i></span>
                        <span class="cm-mobile-link-text">Meu Carrinho (<?php echo esc_html($cart_count); ?>)</span>
                        <span class="cm-mobile-cart-val"><?php echo wp_kses_post($cart_total); ?></span>
                    </a>
                </li>
            </ul>

            <!-- Cartão WhatsApp de Suporte Rápido no Mobile -->
            <div class="cm-mobile-quick-card">
                <div class="cm-quick-card-header">
                    <i class="ri-whatsapp-fill" style="color: #25d366; font-size: 26px;"></i>
                    <div>
                        <strong>Precisa de ajuda com algum MIDI?</strong>
                        <p>Atendimento direto e suporte rápido no WhatsApp</p>
                    </div>
                </div>
                <a href="https://wa.me/5531984511174?text=Oi!%20Estou%20no%20site%20da%20CentralMIDI%20e%20preciso%20de%20aux%C3%ADlio" target="_blank" rel="noopener noreferrer" class="cm-mobile-wa-btn">
                    <i class="ri-chat-1-fill"></i> Iniciar Conversa
                </a>
            </div>

            <!-- Redes Sociais no Rodapé do Menu Mobile -->
            <div class="cm-mobile-footer-socials">
                <span class="cm-mobile-socials-label">Siga a Central MIDI</span>
                <div class="cm-mobile-social-icons">
                    <a href="https://pt-br.facebook.com/centralmidioficial/" target="_blank" rel="noopener nofollow" class="cm-m-social cm-m-fb" aria-label="Facebook">
                        <i class="ri-facebook-fill"></i>
                    </a>
                    <a href="https://www.instagram.com/centralmidioficial/" target="_blank" rel="noopener nofollow" class="cm-m-social cm-m-ig" aria-label="Instagram">
                        <i class="ri-instagram-line"></i>
                    </a>
                    <a href="https://twitter.com/CentralMidi" target="_blank" rel="noopener nofollow" class="cm-m-social cm-m-tw" aria-label="Twitter">
                        <i class="ri-twitter-x-line"></i>
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Lightbox Modal da Newsletter (Preservando formulário nativo) -->
<div id="cm-newsletter-modal" class="cm-modal-overlay" style="display: none;" aria-hidden="true">
    <div class="cm-modal-dialog">
        <button type="button" class="cm-modal-close" id="cm-close-newsletter-modal" aria-label="Fechar newsletter">
            <i class="ri-close-line"></i>
        </button>
        <div class="cm-modal-body">
            <div class="cm-modal-banner">
                <div class="cm-modal-banner-bg" style="background-image: url('https://centralmidi.com.br/wp-content/uploads/2020/05/banjo-1-scaled.jpg');"></div>
                <div class="cm-modal-banner-overlay"></div>
                <div class="cm-modal-banner-content">
                    <span class="cm-banner-badge"><i class="ri-mail-star-line"></i> Central MIDI</span>
                    <h3 class="cm-banner-title">Newsletter</h3>
                    <p class="cm-banner-subtitle">Inscreva-se em nossa lista para receber promoções exclusivas, lançamentos e novidades musicais em primeira mão!</p>
                    
                    <!-- Formulário Nativo -->
                    <?php
                    if (function_exists('cmidi_render_contact_form')) {
                        echo cmidi_render_contact_form();
                    } elseif (shortcode_exists('cmidi_contact_form')) {
                        echo do_shortcode('[cmidi_contact_form]');
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mantém o fallback para links que possam abrir o lightbox antigo #header-newsletter-signup -->
<div id="header-newsletter-signup" class="mfp-hide" style="display:none !important;"></div>
