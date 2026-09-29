# Pendências — Central MIDI

Itens **deliberadamente não corrigidos**, anotados para tratar depois. Todos são
buracos de catálogo, inconsistências do legado e decisões que dependem de fase.
**Nada está quebrando o site agora** — `/ferramentas-admin/` foi reescrito para a
tabela e o erro fatal do `update_field()` não existe mais. O site público está
íntegro.

Cada item traz o estado medido, por que importa, e o que fazer. Ao corrigir, apague
o item e registre o número novo no lugar dos antigos.

## Números de referência

Medidos no staging em 2026-09-28:

| Métrica | Valor |
|---|---|
| Produtos publicados (`wp_posts`) | **81.852** |
| Linhas em `wp_centralmidi_midis` | **81.852** |
| Linhas em `wp_centralmidi_artistas` | **13.625** |
| Gêneros em `wp_centralmidi_generos` | **25** |
| Termos `product_cat` (total) | **13.662** |
| Termos `product_cat` **com produto** | **13.631** |

**Por que 13.625 e não 13.631.** `wp_centralmidi_artistas` liga ao `product_cat`
pelo campo **`nome`**, que tem índice UNIQUE — não existe coluna `term_id`. Os
13.631 termos com produto têm só **13.625 nomes distintos**: 6 pares de homônimos
colapsam numa linha de artista cada. São duetos que o legado gravou como dois
termos:

| Nome (duplicado) | `term_id` | Produtos |
|---|---|---|
| GOSPEL E CATOLICAS | 200866, 200868 | 1.020 |
| LENO E LILIAN | 202982, 208963 | 14 |
| FIORELLO | 200057, 200058 | 16 |
| FERNANDA BRUM E DAMARES | 199975, 199976 | 5 |
| FORROCACANA | 200231, 200232 | 4 |
| CAETANO VELOSO E MORENO VELOSO | 197453, 197454 | 2 |

Nada se perde: os produtos dos dois termos resolvem para a mesma linha de
artista, porque a busca é por nome. `LENO E LILIAN` tem os `term_id` distantes
(202982 e 208963) — um deles veio da importação do legado.

> **Número que não casa com nada:** o antigo cabeçalho dizia "13.627 artistas".
> Esse valor não corresponde a nenhuma métrica da tabela e foi descartado.
>
> **Número válido, mas que muda de significado:** 13.624 é o número de artistas
> **com pelo menos um MIDI**, não o total de artistas (13.625). Os dois aparecem
> neste arquivo em contextos diferentes — não confunda um pelo outro.

---

## 1. A migração não é mais re-executável

**Estado:** resolvido no admin, mas a classe `CentralMidi_Migration` continua
perigosa por desenho.

Os cinco endpoints `pa_admin_*` foram reescritos para `wp_centralmidi_*`. Eles
não leem mais `url_demo` nem `rlm` da postmeta: montam o payload a partir de
`$midi['demo_raw']` e, ao salvar, **apagam** as postmetas legadas
(`functions.php:1039` e `1043`). A única leitura remanescente de demo duplicado
passa por `CentralMidi_DB::get_duplicated_demo_urls()`, isolada no plugin
(`functions.php:1276`). Consequência: **a ordem sugerida na versão anterior deste
item foi seguida — (a) e (b) não existem mais.**

O que sobrou é (c): a migração lê `rlm` e `url_demo` da postmeta para popular
`_centralmidi_classificacao` e `_centralmidi_demo_audio`
(`class-centralmidi-migration.php:554`, `600`, `613`). Como as chaves legadas
foram apagadas em 2026-09-27, um `reset` + novo run **zeria os dois campos em
81.852 produtos**.

**Mitigação já aplicada:** a classe não registra nenhum hook, por decisão
explícita documentada no próprio código (`class-centralmidi-migration.php:64-73`).
O menu e as quatro ações `wp_ajax_*` estão desregistrados de propósito, e o
`reset` derruba as tabelas novas. **Não rodar a migração de novo sem antes
restaurar as duas chaves** — foi exatamente esse re-run que recuperou o staging
depois do acidente de 2026-09-26.

A cópia está em `backup/2026-09-27/metas-acf-dos-produtos.sql` (14 MB, só
`INSERT`, reimportável sem risco de `DROP TABLE`). Para restaurar só a
classificação e o demo:

```sql
-- NÃO importar o arquivo inteiro às cegas; o formato é INSERT-only, mas
-- filtrar evita reinserir meta_id já existentes.
--   rlm      -> _centralmidi_classificacao (via map_classificacao, só M/L/RLM)
--   url_demo -> _centralmidi_demo_audio
```

**Ao reativar a migração:** restaurar as duas chaves primeiro, confirmar
`SELECT COUNT(*) FROM wp_postmeta WHERE meta_key IN ('rlm','url_demo')`, e só
então ligar os hooks.

---

## 2. Arquitetura e Dependência do Tema Pai (Flatsome)

**Estado atual (2026-09-28):** ~80% do sistema é 100% proprietário/customizado e ~20% ainda depende
de componentes estruturais do tema pai Flatsome.

### Inventário de Dependência

1. **Camada Autônoma / Proprietária (~80% - Independente do Flatsome):**
   - **Player de Áudio & Playlist Drawer:** 100% desacoplado em `assets/css/player.css`, `assets/js/custom-script.js` e `howler.min.js`.
   - **Grid & Card de Produto (MIDI):** totalmente customizado em `card-midi.php` e `style.css` (layout Dark Moderno, preview demo, capa em grid side-by-side, badges e botões de ação), com sobrescritas próprias do WooCommerce: `woocommerce/archive-product.php`, `woocommerce/content-product.php`, `woocommerce/loop/*` e `woocommerce/single-product/related.php`.
   - **Camada de Dados & Queries:** classe `CentralMidi_DB` com queries SQL nativas e otimizadas sem hooks do Flatsome.
   - **Listagens e Arquivos:** `search.php` e taxonomias (`taxonomy-product_cat.php`, `taxonomy-genero_musical.php`, `taxonomy-mes_de_lancamento.php`) com grid próprio, banners dark dinâmicos e controle de fila de reprodução.
   - **Catálogo A-Z e Navegação de Artistas:** 100% no plugin (`class-centralmidi-artistas.php`), com navegação A-Z em AJAX instantâneo, busca em tempo real, SSR e aviso #RLM.
   - **Gerenciador de Carrinho AJAX:** `assets/js/cart-manager.js`.
   - **Cabeçalho — conteúdo (desde 2026-09-28):** `template-parts/header/header-wrapper.php` próprio do child: topbar (WhatsApp, redes sociais, newsletter com formulário nativo), header principal (logo, links diretos com ícones, busca com dropdown/AJAX, conta, carrinho e theme switcher) e **menu mobile em tela cheia** (`cm-mobile-fullscreen`) com busca, accordion e sub-links — o off-canvas do Flatsome foi removido (`remove_action('wp_footer','flatsome_mobile_menu',7)`). **Resíduo:** só o shell do `header.php` do pai — ver item 2.
   - **Rodapé Global:** ~100% desacoplado em 2026-09-28 — `footer.php` próprio no child, back-to-top com ícone Remix e handler nativo. **Resíduo só de CSS:** o botão usa classes utilitárias do tema (ver passo 5).
   - **Home (conteúdo):** as 4 seções abaixo de `get_featured` (Bem-vindos, Sobre os serviços, Nossa equipe, Leis de Copyright) são estáticas `cm-home-*` e tokenizadas desde 2026-09-28.
   - **Fluxos WooCommerce (parciais):** o child sobrescreve `cart/cart.php`, `checkout/thankyou.php`, `checkout/layouts/checkout-focused.php` e `order/order-details-item.php`.

2. **Camada Dependente do Flatsome (~20% - Amarrada ao Tema Pai):**
   - **Cabeçalho — shell do `header.php`:** o template raiz ainda é do pai (`<header id="header" class="header ...">` + `flatsome_header_classes()` + `do_action('flatsome_before/after_header')`) e envolve o `header-wrapper.php` custom. O hook `flatsome_header_elements` ainda é usado (`functions.php:89`) para injetar o theme-toggle — funcionalmente redundante, pois o wrapper custom tem botão próprio.
   - **Página Individual do Produto:** sem `single-product.php` próprio — o layout principal vem do pai (`woocommerce/single-product.php` + `single-product/layouts/*`); o child só injeta hooks (`woocommerce_single_product_summary` 15 e 18) e sobrescreve `related.php`.
   - **Minha Conta:** `woocommerce/myaccount/*` (navegação, dashboard, login) ainda do pai — o child não sobrescreve.
   - **Página da Home (wrapper + hero):** `page-blank.php` próprio sem hooks do pai, mas o `post_content` (page 14) guarda shortcodes do UX Builder (`[ux_slider]` com 9 `[ux_banner]` e o wrapper `[section][get_featured]`) cujas classes de tema (`slider-wrapper`+flickity, parallax) saem do `flatsome.css` — ver passo 4.
   - **CSS/JS Base:** `flatsome.css` (~400KB) e scripts do UX Builder + resets de formulário, fontes de ícones e modais herdados.

**Por que importa:**
- Se o tema pai Flatsome for desativado hoje, a aplicação quebra no shell do cabeçalho, na página individual de produto, na Minha Conta e no hero da home.
- O tema pai injeta CSS (~400KB+) e scripts do UX Builder que geram overhead de carregamento, mesmo que a maior parte da experiência de compra e reprodução já utilize código enxuto e moderno.

**Ao corrigir (Roteiro para Desacoplamento 100%):**
1. **Cabeçalho Nativo (falta só o shell):** criar `header.php` no child que apenas inclua `template-parts/header/header-wrapper.php`, eliminando as tags e classes do pai (`<header id="header" class="header ...">`, `flatsome_header_classes()`, `do_action('flatsome_before/after_header')`), e remover o gancho `flatsome_header_elements` (toggle já está no wrapper). O conteúdo já é 100% child.
2. **Rodapé Nativo:** ~~Criar `footer.php` enxuto~~ **FEITO (2026-09-28).** `flatsome-child/footer.php` substituiu a casca do pai; o back-to-top ganhou handler próprio e ícone Remix. Ao completar o passo 5, remover as classes utilitárias do Flatsome do botão e portar o estilo para o `style.css` do child.
3. **Página de Produto Único (`single-product.php`):** sobrescrever o template principal de produto do WooCommerce (hoje do pai) com visual Dark Moderno, player em destaque, lista de faixas/amostra e recomendações em `card-midi.php` — hoje o child só injeta o player de áudio e o aviso RLM via hooks.
4. **Substituição do Slider UX Builder:** a home guarda **shortcodes do UX Builder** no `post_content` (page 14) — `[ux_slider]` com 9 `[ux_banner]` e o wrapper `[section][get_featured]` dos últimos lançamentos. **Já migradas (2026-09-28):** as 4 seções abaixo de `get_featured` (Bem-vindos, Sobre os serviços, Nossa equipe, Leis de Copyright) viraram HTML estático próprio `cm-home-*` com CSS no `style.css` do child — e suas cores foram tokenizadas (`--cm-home-*` nos escopos dark e light; valores idênticos hoje por decisão de design). Falta trocar o **hero** (slider/flickity + 9 banners, parallax) por markup próprio — e então portar o estilo restante para o `style.css`. Obs.: o toggle light hoje não muda o fundo da página porque `custom-style.css:11` força `#080c13` no `html,body` — revisar esse `!important` se quiser que o tema claro tenha efeito global. **Bugfix ligado ao light:** `cm-btn-buy` no card de música ficava invisível no claro (card branco via `var(--cm-bg-card)` × texto `#f8fafc` fixo) — corrigido com override `[data-theme="light"] .cm-track-card .cm-btn-buy` (ver HISTORICO §1.14).
5. **Autonomia de Tema:** renomear o tema para `centralmidi` no `style.css` (removendo `Template: flatsome`), tornando o projeto 100% autônomo e livre de licenças ou atualizações que possam sobrescrever estilos. Aproveitar para remover o gancho `flatsome_header_elements` remanescente.

## Como usar este arquivo

- Item corrigido → apague o bloco, não marque como "feito". **Este diretório não é
  repo git**, então o histórico vai para `HISTORICO-E-ARMADILHAS.md` (§1.x) e o
  dump correspondente em `backup/`. O runbook da migração foi removido em
  2026-09-28; o backup dele está em `atual/.local-excluidos/docs-2026-09-28/`.
- Item que virou irrelevante → apague também, e diga por quê em `HISTORICO-E-ARMADILHAS.md`.
- Achado novo → adicione no fim, com número de seção e medição.
- Ao mexer em `product_cat`, meça antes e depois. Os números deste arquivo
  servem de linha de base; se mudarem, atualize o cabeçalho.

