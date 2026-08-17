<?php
/**
 * Template Part: Hero Carousel
 *
 * Renders published cm_slide posts as a carousel.
 * Falls back to the static hero when there are no slides.
 */
$slides = function_exists('centralmidi_get_slides') ? centralmidi_get_slides() : array();

if (empty($slides)) :
    ?>
    <div class="cm-hero-banner">
        <div class="cm-container">
            <span class="cm-badge"><i class="ri-disc-line"></i> Catálogo Oficial Central MIDI</span>
            <h1>Catálogo de Arquivos MIDI & Playbacks</h1>
            <p>Ouça as demonstrações em áudio MP3, filtre por classificação <strong>#M / #L / #RLM</strong>, artista e gênero, e compre online com download instantâneo.</p>
        </div>
    </div>
    <?php
    return;
endif;
?>

<div class="cm-carousel" data-interval="6000">
    <div class="cm-carousel-track">
        <?php
        $index = 0;
        foreach ($slides as $slide) {
            $slide_id  = $slide->ID;
            $badge     = get_post_meta($slide_id, '_cm_slide_badge', true);
            $subtitle  = get_post_meta($slide_id, '_cm_slide_subtitle', true);
            $btn_text  = get_post_meta($slide_id, '_cm_slide_btn_text', true);
            $btn_url   = get_post_meta($slide_id, '_cm_slide_btn_url', true);
            $align     = get_post_meta($slide_id, '_cm_slide_align', true);
            $align     = in_array($align, array('left', 'center', 'right'), true) ? $align : 'left';
            $tonalidade = get_post_meta($slide_id, '_cm_slide_tonalidade', true);
            $tonalidade = in_array($tonalidade, array('dark', 'light'), true) ? $tonalidade : 'dark';
            $image     = get_the_post_thumbnail_url($slide_id, 'large');
            $title     = get_the_title($slide_id);

            $slide_class = 'cm-carousel-slide cm-carousel-tonal--' . $tonalidade;
            if (0 === $index) {
                $slide_class .= ' is-active';
            }
            ?>
            <div class="<?php echo esc_attr($slide_class); ?>" style="<?php echo $image ? 'background-image:url(' . esc_url($image) . ');' : ''; ?>">
                <div class="cm-carousel-overlay"></div>
                <div class="cm-container cm-carousel-content cm-carousel-content--<?php echo esc_attr($align); ?>">
                    <?php if ($badge) : ?>
                        <span class="cm-carousel-badge"><i class="ri-disc-line"></i> <?php echo esc_html($badge); ?></span>
                    <?php endif; ?>
                    <h1 class="cm-carousel-title"><?php echo esc_html($title); ?></h1>
                    <?php if ($subtitle) : ?>
                        <p class="cm-carousel-subtitle"><?php echo esc_html($subtitle); ?></p>
                    <?php endif; ?>
                    <?php if ($btn_text && $btn_url) : ?>
                        <a class="cm-btn cm-btn-primary cm-carousel-cta" href="<?php echo esc_url($btn_url); ?>">
                            <i class="ri-arrow-right-line"></i> <?php echo esc_html($btn_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $index++;
        }
        ?>
    </div>

    <?php if (count($slides) > 1) : ?>
        <button type="button" class="cm-carousel-arrow cm-carousel-prev" aria-label="<?php esc_attr_e('Anterior', 'central-midi'); ?>">
            <i class="ri-arrow-left-s-line"></i>
        </button>
        <button type="button" class="cm-carousel-arrow cm-carousel-next" aria-label="<?php esc_attr_e('Próximo', 'central-midi'); ?>">
            <i class="ri-arrow-right-s-line"></i>
        </button>

        <div class="cm-carousel-dots">
            <?php for ($i = 0; $i < count($slides); $i++) : ?>
                <button type="button" class="cm-carousel-dot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($i); ?>" aria-label="<?php echo esc_attr(sprintf(__('Ir para o slide %d', 'central-midi'), $i + 1)); ?>"></button>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>