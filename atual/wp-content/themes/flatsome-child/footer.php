<?php
/**
 * Footer do site — template próprio do child theme.
 *
 * Substitui flatsome/footer.php. Antes o rodapé vinha inteiro do tema pai:
 * este arquivo não existia aqui, e o WordPress caía no `flatsome/footer.php`,
 * que abria a casca e disparava `do_action('flatsome_footer')`.
 *
 * O conteúdo do rodapé já era do child — `functions.php` removia
 * `flatsome_page_footer` do hook e injetava o próprio template part. O que
 * faltava era a casca, e ela é o que está aqui agora.
 *
 * Dependências do Flatsome removidas com este arquivo:
 *   - flatsome/footer.php              (a casca de 12 linhas)
 *   - o hook flatsome_footer           (o child era o único consumidor dele)
 *   - flatsome_page_footer             (já era removido em functions.php)
 *   - flatsome_go_to_top               (reimplementado abaixo, sem o template part)
 *   - flatsome_html_atts()             (helper do pai para montar o botão)
 *   - get_flatsome_icon() + fonte fl-icons  (ícone trocado por Remix Icon)
 *
 * Dependência de CSS que AINDA existe, de propósito: as classes utilitárias do
 * botão (`button icon invert plain fixed bottom z-1 is-outline circle`) são do
 * Flatsome, então o visual dele vem do CSS do tema pai enquanto ele estiver
 * ativo. Ao remover o tema (PENDENCIAS §6, passo 5), portar esse estilo para o
 * style.css do child e largar as classes utilitárias.
 *
 * A estrutura de tags reproduz a do pai de propósito: o `</div>` fecha o
 * wrapper aberto pelo header, e a ordem dos filhos dentro de <footer> é a mesma
 * que o pai produzia (back-to-top antes do conteúdo).
 *
 * @package CentralMidi_Child
 */

?>
</main>

<footer id="footer" class="footer-wrapper">
	<?php
	/**
	 * Botão "voltar ao topo".
	 *
	 * Reproduz o output de flatsome/template-parts/footer/back-to-top.php
	 * (mesmas classes, na mesma ordem) porque 6 regras de CSS do próprio child
	 * miram `#top-link.back-to-top`: style.css:442 e :452, e player.css:653,
	 * :655, :874, :876 — as duas últimas reposicionam o botão quando o player
	 * sticky está ativo. Trocar o id ou as classes quebraria todas.
	 *
	 * A única troca real é o ícone: `icon-angle-up` vem da fonte `fl-icons`,
	 * definida em flatsome/assets/css/flatsome.css. Trocado por
	 * `ri-arrow-up-line` (Remix Icon, que o tema já carrega do CDN e usa em
	 * todos os botões próprios). A medição por tinta no canvas deu 13x8 para
	 * o ícone antigo a 22px; o arrow-up-line a 18px ocupa ~13x12 — mesma
	 * largura, um pouco mais alto por ser uma seta de verdade.
	 */
	if ( get_theme_mod( 'back_to_top', 1 ) ) {
		$cm_btt_classes = array(
			'back-to-top',
			'button',
			'icon',
			'invert',
			'plain',
			'fixed',
			'bottom',
			'z-1',
			'is-outline',
		);

		$cm_btt_classes[] = ( 'circle' === get_theme_mod( 'back_to_top_shape', 'circle' ) )
			? 'circle'
			: 'round';

		if ( 'left' === get_theme_mod( 'back_to_top_position' ) ) {
			$cm_btt_classes[] = 'left';
		}

		if ( ! get_theme_mod( 'back_to_top_mobile' ) ) {
			$cm_btt_classes[] = 'hide-for-medium';
		}

		printf(
			'<button type="button" id="top-link" class="%1$s" aria-label="%2$s"><i class="%3$s" aria-hidden="true"></i></button>',
			esc_attr( implode( ' ', $cm_btt_classes ) ),
			esc_attr__( 'Ir para o topo', 'flatsome-child' ),
			esc_attr( 'ri-arrow-up-line' )
		);
	}

	// Conteúdo do rodapé: sempre o template part do child, nunca o do pai.
	get_template_part( 'template-parts/footer/footer-modern' );
	?>
</footer>

</div>
<?php wp_footer(); ?>
</body>
</html>
