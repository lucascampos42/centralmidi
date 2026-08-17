<?php
/**
 * Page Template for Central Midi Theme
 */
get_header(); ?>

<div class="cm-container cm-page-wrap">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <h1 class="cm-page-title"><?php the_title(); ?></h1>
            <div class="cm-page-content">
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; endif; ?>
</div>

<?php get_footer(); ?>