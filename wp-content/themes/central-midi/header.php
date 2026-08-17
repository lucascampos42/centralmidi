<?php
/**
 * Header Template - Central Midi
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    (function () {
        try {
            var theme = localStorage.getItem('cm-theme');
            var root = document.documentElement;
            if (theme === 'light') {
                root.classList.add('cm-theme-light');
            } else if (theme === 'dark') {
                root.classList.add('cm-theme-dark');
            }
        } catch (e) {}
    })();
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="cm-skip-link" href="#cm-main"><?php esc_html_e('Pular para o conteúdo', 'central-midi'); ?></a>

<header class="cm-header">
    <div class="cm-container cm-header-content">
        <div class="cm-header-brand-group">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="cm-logo" aria-label="<?php esc_attr_e('Central Midi - Início', 'central-midi'); ?>">
                <img class="cm-logo-img" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo.webp'); ?>" alt="Central MIDI" width="150" height="39" />
            </a>

            <div class="cm-header-search">
                <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <i class="ri-search-line"></i>
                    <label class="cm-visually-hidden" for="cm-search-input"><?php esc_html_e('Buscar MIDIs', 'central-midi'); ?></label>
                    <input type="search" id="cm-search-input" placeholder="Buscar música, artista..." value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
                </form>
            </div>
        </div>

        <nav id="cm-primary-nav" class="cm-nav" aria-label="<?php esc_attr_e('Menu principal', 'central-midi'); ?>">
            <ul class="cm-nav-list">
                <li class="cm-nav-search">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <i class="ri-search-line"></i>
                        <label class="cm-visually-hidden" for="cm-search-input-mobile"><?php esc_html_e('Buscar MIDIs', 'central-midi'); ?></label>
                        <input type="search" id="cm-search-input-mobile" placeholder="Buscar música, artista..." value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
                    </form>
                </li>
                <li class="cm-nav-item">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="cm-nav-link <?php echo (is_front_page() || is_home()) ? 'active' : ''; ?>">
                        <i class="ri-home-5-line"></i> Início
                    </a>
                </li>
                <li class="cm-nav-item">
                    <a href="<?php echo esc_url(home_url('/servicos/')); ?>" class="cm-nav-link <?php echo is_page('servicos') ? 'active' : ''; ?>">
                        <i class="ri-service-line"></i> Serviços
                    </a>
                </li>
                <li class="cm-nav-item cm-dropdown">
                    <a href="<?php echo esc_url(centralmidi_catalog_url()); ?>" class="cm-nav-link cm-dropdown-toggle <?php echo is_page('midis') ? 'active' : ''; ?>">
                        <i class="ri-folder-music-line"></i> MIDIs <i class="ri-arrow-down-s-line cm-arrow"></i>
                    </a>
                    <ul class="cm-dropdown-menu">
                        <li>
                            <a href="<?php echo esc_url(centralmidi_catalog_url()); ?>">
                                <i class="ri-apps-2-line"></i> Ver Todos os MIDIs
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/artistas/')); ?>">
                                <i class="ri-user-star-line"></i> Artistas (A-Z)
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(centralmidi_catalog_url() . '?filter=genero'); ?>">
                                <i class="ri-music-2-line"></i> Gêneros
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="cm-nav-item">
                    <a href="<?php echo esc_url(home_url('/#contato')); ?>" class="cm-nav-link">
                        <i class="ri-mail-line"></i> Contato
                    </a>
                </li>
                <li class="cm-nav-item cm-nav-mobile-only">
                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cm-nav-link">
                        <i class="ri-shopping-cart-2-line"></i> Carrinho (<?php echo (function_exists('WC') && is_object(WC()->cart)) ? esc_html(WC()->cart->get_cart_contents_count()) : '0'; ?>)
                    </a>
                </li>
                <li class="cm-nav-item cm-nav-mobile-only">
                    <?php if (is_user_logged_in()) : ?>
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="cm-nav-link">
                            <i class="ri-user-smile-line"></i> Minha Conta
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="cm-nav-link">
                            <i class="ri-user-line"></i> Fazer Login
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>

        <div class="cm-header-actions">
            <button type="button" class="cm-theme-toggle" id="cm-theme-toggle" aria-label="Alternar tema" title="Alternar tema">
                <i class="ri-contrast-2-line" id="cm-theme-icon"></i>
            </button>

            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="cm-header-cart-btn" aria-label="<?php esc_attr_e('Carrinho de compras', 'central-midi'); ?>" title="Carrinho">
                <i class="ri-shopping-cart-2-line"></i>
                <span class="cm-cart-count"><?php echo (function_exists('WC') && is_object(WC()->cart)) ? esc_html(WC()->cart->get_cart_contents_count()) : '0'; ?></span>
            </a>

            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="cm-header-user-btn logged-in" title="Minha Conta">
                    <i class="ri-user-smile-line"></i>
                    <span>Minha Conta</span>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="cm-header-user-btn" title="Entrar na conta">
                    <i class="ri-user-line"></i>
                    <span>Entrar</span>
                </a>
            <?php endif; ?>

            <button type="button" class="cm-menu-toggle" id="cm-menu-toggle" aria-controls="cm-primary-nav" aria-expanded="false" aria-label="<?php esc_attr_e('Abrir menu', 'central-midi'); ?>">
                <i class="ri-menu-line cm-menu-open-icon"></i>
                <i class="ri-close-line cm-menu-close-icon"></i>
            </button>
        </div>
    </div>
</header>

<main id="cm-main" class="cm-main-content">
