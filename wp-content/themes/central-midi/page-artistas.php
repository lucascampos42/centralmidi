<?php
/**
 * Template Name: Página de Artistas (A-Z)
 * Template Post Type: page
 */
get_header();

$selected_letter = isset($_GET['letra']) ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['letra'])))) : '';
$search_query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$selected_artist = isset($_GET['artista']) ? sanitize_text_field(wp_unslash($_GET['artista'])) : '';

$artistas = class_exists('CentralMidi_DB') ? CentralMidi_DB::get_artistas_alfabetico($selected_letter, $search_query) : array();

// Prepares A-Z alphabet
$alphabet = range('A', 'Z');
?>

<div class="cm-hero-banner">
    <div class="cm-container">
        <span class="cm-badge"><i class="ri-user-star-line"></i> Repertório por Intérprete</span>
        <h1 class="cm-hero-title">Buscar MIDIs por Artista</h1>
        <p class="cm-hero-subtitle">
            Selecione uma letra de <strong>A a Z</strong> ou use a barra de busca para encontrar todos os playbacks e arranjos do seu cantor ou banda favorita.
        </p>

        <div class="cm-search-form">
            <form method="get" action="<?php echo esc_url(home_url('/artistas/')); ?>">
                <?php if ($selected_letter) : ?>
                    <input type="hidden" name="letra" value="<?php echo esc_attr($selected_letter); ?>">
                <?php endif; ?>
                <div class="cm-search-field">
                    <i class="ri-search-line"></i>
                    <input type="text" name="q" value="<?php echo esc_attr($search_query); ?>" placeholder="Digite o nome do artista..." />
                </div>
                <button type="submit" class="cm-btn cm-btn-primary">
                    <i class="ri-search-line"></i> Buscar
                </button>
            </form>
        </div>
    </div>
</div>

<div class="cm-container cm-artistas-page">
    <div class="cm-az-bar-wrapper">
        <div class="cm-az-bar">
            <a href="<?php echo esc_url(home_url('/artistas/')); ?>"
               class="cm-az-box <?php echo (empty($selected_letter) && empty($search_query)) ? 'active' : ''; ?>">
                TODOS
            </a>

            <a href="<?php echo esc_url(add_query_arg('letra', '0-9', home_url('/artistas/'))); ?>"
               class="cm-az-box <?php echo ($selected_letter === '0-9') ? 'active' : ''; ?>"
               title="Números e Símbolos">
                0-9 / #
            </a>

            <?php foreach ($alphabet as $letter) : ?>
                <a href="<?php echo esc_url(add_query_arg('letra', $letter, home_url('/artistas/'))); ?>"
                   class="cm-az-box <?php echo ($selected_letter === $letter) ? 'active' : ''; ?>">
                    <?php echo esc_html($letter); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($selected_artist)) :
        $artist_product_ids = class_exists('CentralMidi_DB') ? CentralMidi_DB::search_product_ids(array('artista' => $selected_artist)) : array();
    ?>
        <div class="cm-box cm-box--pad cm-artist-detail">
            <div class="cm-artist-detail-header">
                <div>
                    <span class="cm-badge"><i class="ri-music-fill"></i> Discografia MIDI</span>
                    <h2 class="cm-artist-detail-title">
                        Músicas de: <span class="cm-artist-detail-name"><?php echo esc_html($selected_artist); ?></span>
                    </h2>
                </div>
                <a href="<?php echo esc_url(home_url('/artistas/')); ?>" class="cm-btn cm-btn-outline">
                    <i class="ri-arrow-left-line"></i> Voltar à lista de artistas
                </a>
            </div>

            <?php if (!empty($artist_product_ids)) : ?>
                <div class="cm-tracks-grid">
                    <?php foreach ($artist_product_ids as $pid) : ?>
                        <?php get_template_part('template-parts/card-midi', null, array('product_id' => $pid)); ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="cm-empty-note">Nenhum arquivo MIDI encontrado para este artista.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="cm-list-header">
        <h3 class="cm-list-title">
            <?php
            if (!empty($search_query)) {
                echo 'Resultados para "' . esc_html($search_query) . '"';
            } elseif (!empty($selected_letter)) {
                echo 'Artistas com a letra: <span class="cm-list-highlight">' . esc_html($selected_letter) . '</span>';
            } else {
                echo 'Todos os Artistas';
            }
            ?>
            <span class="cm-list-count">(<?php echo count($artistas); ?> encontrados)</span>
        </h3>

        <?php if (!empty($selected_letter) || !empty($search_query)) : ?>
            <a href="<?php echo esc_url(home_url('/artistas/')); ?>" class="cm-btn cm-btn-outline cm-btn-clear">
                <i class="ri-close-line"></i> Limpar Filtro
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($artistas)) : ?>
        <div class="cm-artists-grid">
            <?php foreach ($artistas as $art) :
                $count = (int)$art->total_midis;
                $artist_name = $art->nome;
                $active_class = ($selected_artist === $artist_name) ? 'selected' : '';
            ?>
                <a href="<?php echo esc_url(add_query_arg('artista', urlencode($artist_name), home_url('/artistas/'))); ?>"
                   class="cm-artist-pill <?php echo esc_attr($active_class); ?>">
                    <div class="cm-artist-pill-avatar">
                        <i class="ri-user-voice-line"></i>
                    </div>
                    <div class="cm-artist-pill-info">
                        <span class="cm-artist-pill-name"><?php echo esc_html($artist_name); ?></span>
                        <span class="cm-artist-pill-count">
                            <?php echo esc_html($count); ?> <?php echo ($count === 1) ? 'MIDI' : 'MIDIs'; ?>
                        </span>
                    </div>
                    <i class="ri-arrow-right-s-line cm-artist-pill-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="cm-empty-card">
            <i class="ri-user-unfollow-line"></i>
            <h4>Nenhum artista encontrado</h4>
            <p>Não encontramos nenhum artista com a letra ou termo pesquisado.</p>
            <a href="<?php echo esc_url(home_url('/artistas/')); ?>" class="cm-btn cm-btn-primary">
                Ver todos os artistas
            </a>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>