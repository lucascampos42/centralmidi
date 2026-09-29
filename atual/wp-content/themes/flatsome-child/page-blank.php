<?php
/**
 * Template name: Page — Full Width (próprio do child theme)
 *
 * Substitui flatsome/page-blank.php para as páginas que usam "Full Width":
 * hoje só a home (page 14) e `servicos` (page 91).
 *
 * O do tema abria a página com `do_action( 'flatsome_before_page' )` e fechava
 * com `do_action( 'flatsome_after_page' )`. Aqui não há nenhuma chamada ao
 * tema: o arquivo só entrega o container + o conteúdo + o rodapé (que já é do
 * child, via footer.php). Os listeners desses hooks não emitem nada para essas
 * duas páginas (sem excerpt, sem senha, sem paginação, comentários fechados),
 * então o HTML renderizado é idêntico ao do template do pai.
 *
 * @package CentralMidi_Child
 */

get_header();
?>
<div id="content" role="main" class="content-area">

	<?php while ( have_posts() ) : the_post(); ?>

		<?php the_content(); ?>

	<?php endwhile; ?>

</div>
<?php get_footer(); ?>