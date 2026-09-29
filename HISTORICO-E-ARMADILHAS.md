# Histórico da migração — armadilhas e itens em aberto

Extraído de `MIGRACAO-BANCO-PRODUCAO.md` (runbook removido em 2026-09-28, com a
migração já concluída e validada). O runbook em si foi apagado de propósito: ele
continuava com um "Rollback" contendo `DROP TABLE` e um passo 9 destrutivo, e
reexecutá-lo agora zeraria campos de 81.852 produtos — as chaves legadas que ele
precisa já foram apagadas.

Ficaram aqui as duas coisas que **não** se reconstroem sozinhas:

1. as armadilhas que quebraram o projeto durante o desenvolvimento;
2. os itens do checklist final que continuam abertos.

Backup do runbook completo: `.local-excluidos/docs-2026-09-28/MIGRACAO-BANCO-PRODUCAO.md`.

Números de referência da migração: **81.852** MIDIs, **13.625** artistas,
**25** gêneros, **13.662** termos em `product_cat`.

---

# 1. Armadilhas

> Estas 13 coisas quebraram de verdade durante o desenvolvimento. Estão aqui para
> ninguém reintroduzir o mesmo bug.

## 1.1 "Artista" NÃO é "termo com pai" — é "termo com produto"

O código original filtrava `tt.parent <> 0`, assumindo que toda raiz de
`product_cat` é uma letra do A-Z. **Isso está errado.**

As 29 raízes de agrupamento (A-Z, 0-9, SEM-CATEGORIA) têm **0 produtos
diretos** — são nós puros. Já 118 artistas reais estavam gravados como raiz
(herança de dados velhos), e o filtro os descartava silenciosamente:
**159 produtos ficavam sem artista e 118 artistas sumiam do site inteiro.**

Regra correta, em qualquer query que liste artistas de `product_cat`:

```sql
SELECT tt.term_id, t.name,
       (SELECT COUNT(*) FROM wp_term_relationships tr
         WHERE tr.term_taxonomy_id = tt.term_taxonomy_id) AS qtd_produtos
FROM wp_term_taxonomy tt
JOIN wp_terms t ON t.term_id = tt.term_id
WHERE tt.taxonomy = 'product_cat'
  AND EXISTS (SELECT 1 FROM wp_term_relationships tr
               WHERE tr.term_taxonomy_id = tt.term_taxonomy_id)
ORDER BY t.name;
```

Essa query devolve **13.631** linhas: 13.513 filhos com produto + 118
raízes-artista. Conferido em 2026-09-28 sobre 13.662 termos, dos quais 29 são
letras sem produto e 2 são filhos sem produto. Os números que indicam filtro
errado: **13.515** (contou todos os filhos, incluindo os 2 vazios), **13.513**
(filtro `parent <> 0`, que joga fora as 118 raízes-artista) ou **147**
(somou as 29 letras).

> `A` tem 980 filhos e 0 produtos diretos: é agregado, não artista. Por isso ele
> **não** deve aparecer na listagem (no site legado aparecia como
> "A — 5.340 MIDIs", o que é enganoso).

## 1.2 Artista duplicado: vence o termo mais usado

O produto 1146331 ("Andre Leono/Laura") está ligado a dois termos:
`ANDRE LEONO` (canônico, 2 produtos) e `ANDRE LEONNO` (typo, 1 produto).
Desempate por menor `term_id` premiava o typo e roubava um MIDI do artista
certo. A ordenação correta, na query que resolve o artista de cada produto:

```sql
SELECT tr.object_id, t.term_id, t.name,
       (SELECT COUNT(*) FROM wp_term_relationships tr2
         WHERE tr2.term_taxonomy_id = tt.term_taxonomy_id) AS qtd
FROM wp_term_relationships tr
JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
JOIN wp_terms        t  ON t.term_id = tt.term_id
WHERE tt.taxonomy = 'product_cat'
ORDER BY tr.object_id, qtd DESC, t.term_id;
```

Só existe **1** produto assim em 81.852, mas o sintoma (contagem errada na letra
correspondente) aparece em qualquer catálogo com typo.

## 1.3 Bucket `#` (artistas que começam com dígito)

`/artistas/outros/` (o `#` do A-Z) tem que receber tudo que **não** começa com
A-Z — incluindo `3 DOORS DOWN`, `50 CENT`, `10 CC`. São 33 artistas.

O erro clássico é filtrar `^[A-Z]` **antes** de tratar o caso `#`, o que joga
esses 33 fora e deixa a página permanentemente vazia. O `#` precisa de lógica
invertida:

```php
if ($letra_filtro === '#') {
    if (preg_match('/^[A-Z]$/', $pl)) continue;   // é letra, não é "outros"
} else {
    if (!preg_match('/^[A-Z]$/', $pl)) continue;
    if ($pl !== $letra_filtro) continue;
}
```

## 1.4 Nome de termo com entidade HTML

`wp_terms.name` tem `PORGY &amp; BESS` — **com a entidade gravada no dado**, não
escapada na saída. São 2 casos (`PORGY & BESS`, `R. & R. SHERMAN`).

Consequência: montar a URL de busca com `rawurlencode($nome)` gera
`?s=PORGY%20%26amp%3B%20BESS`, e a busca procura literalmente por `&amp;` e não
acha o MIDI. Descriptografe antes de codificar:

```php
$search = html_entity_decode($nome, ENT_QUOTES, 'UTF-8');
$url = add_query_arg('s', rawurlencode($search), home_url('/'));
```

Não "conserte" o dado em `wp_terms` — a migração é aditiva por desenho.

## 1.5 707 produtos sem gênero é o resultado correto

`genero_id = 0` em 707 produtos porque **a fonte não tem gênero**: nem a postmeta
`_centralmidi_genero`, nem termo em `genero_musical`. Não é bug, não é perda.
A lista de gêneros com contagem (inclusive as 7 categorias vazias) precisa apenas
bater com o legado.

## 1.6 Ordem de `information_schema`

Para ler a collation de uma tabela, o join tem que ser em `COLUMNS`:

```sql
-- ERRADO: CHARACTER_SET_NAME não existe em TABLES.
-- ERROR 1054 (42S22): Unknown column 'CHARACTER_SET_NAME' in 'SELECT'
SELECT CHARACTER_SET_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wp_posts';

-- CERTO: a charset mora em COLUMNS; o join usa a collation como ponte.
SELECT c.CHARACTER_SET_NAME, t.TABLE_COLLATION
FROM information_schema.TABLES t
JOIN information_schema.COLUMNS c
  ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME
 AND c.COLLATION_NAME = t.TABLE_COLLATION
WHERE t.TABLE_SCHEMA = DATABASE() AND t.TABLE_NAME = 'wp_posts';
```

**Por que isso quase passou despercebido:** a query dá erro, mas no PHP
`$wpdb->get_row()` devolve `null` em vez de lançar exceção. A primeira versão do
`align_collations()` fazia `if (!$reference) return;` sem registrar nada em log,
e o `maybe_upgrade()` carimbava `centralmidi_db_version` assim mesmo. Resultado:
a collation ficava errada para sempre, sem erro visível, e só explodia semanas
depois em `ERROR 1267` dentro de uma query que ninguém suspeitava.

Por isso a versão atual faz `error_log()` nesse caminho. Se você ver
`CentralMidi: align_collations() could not read the reference collation` no
`debug.log`, **não ignore** — a conversão não rodou.

## 1.7 Prioridade no `template_redirect`

`inc/cmidi-artistas.php` NÃO é um template comum. Ele sequestra a requisição:

```php
add_action('template_redirect', 'cmidi_child_template_redirect_stable', 5);
function cmidi_child_template_redirect_stable() {
    // ...
    include($template);   // page-artistas.php
    exit;                 // <-- encerra TUDO
}
```

Como ele roda na **prioridade 5 e chama `exit`**, qualquer callback de 404
registrado na prioridade padrão (10) **nunca executa** nas URLs `/artistas/...` e
`/midis/...`. Do lado de fora parece que o guard "não funciona"; na verdade ele nem
chega a ser chamado. Por isso o guard de 404 é registrado com
`add_action('template_redirect', 'cmidi_legacy_tax_404', 1)`.

Sintoma de diagnóstico: o 404 funciona em `/genero_musical/x/` e `/tipo/midi/`,
mas `/artistas/<slug>/` continua 200 — e o `is_tax()` está correto. É a
assinatura desse problema.

## 1.8 "Destaques" da home é uma seleção arbitrária (e isso é esperado)

`query_music_featured()` pega 30 MIDIs de cada um dos 3 últimos meses com
`get_midis_by_month($mes, $ano, 30)`, que ordena por `created_at DESC,
product_id DESC`. Os 30 saem de uma vez, e o critério é arbitrário:

- todos os 81.852 produtos têm `post_date` **igual** (`2026-01-16`, a data da
  importação em lote), então `ORDER BY post_date` não distingue nada;
- `menu_order` é `0` em todos;
- `created_at` é o timestamp da **migração** — toda vez que a migração roda de
  novo, todos os valores mudam e a seleção muda.

Ou seja: rodar a migração de novo troca os 30 produtos exibidos em cada mês
**sem que nada tenha mudado no site**. Não é bug, e não dá para reproduzir o
resultado do baseline byte a byte. Se isso incomodar, troque o critério por algo
semântico (`product_id ASC` para ser estável, ou um meta de destaque) — mas saiba
que o comportamento antigo era igualmente arbitrário.

Pelo mesmo motivo, **nunca compare "produtos relacionados" contra baseline**: o
WooCommerce usa `orderby => 'rand'`, e 3 requisições seguidas já devolvem 3
conjuntos diferentes.

## 1.9 NUNCA reimporte um `mysqldump` para "validar" o backup

Esta é a armadilha que destruiu o staging em 2026-09-26. O `mysqldump` escreve
no topo do arquivo:

```sql
DROP TABLE IF EXISTS `wp_posts`;
CREATE TABLE `wp_posts` ( ... );
INSERT INTO `wp_posts` VALUES ...
```

Então "testar se o backup é válido" importando o arquivo **não é um teste: é uma
execução**. Com um `--where` restrito, o dump recria as tabelas contendo só as
linhas do filtro. Foi o que aconteceu: o backup de validação do ACF levava
`DROP TABLE` + `CREATE TABLE` de `wp_posts` e `wp_postmeta`, a reimportação
destruiu as duas tabelas (2,2 milhões de linhas de postmeta, incluindo as
736.668 `_centralmidi_*`), e a home/produto/carrinho caíram para 404.

Para validar um dump com segurança:

```bash
# 1. confira se o arquivo tem DROP/CREATE (se tiver, NUNCA importe na base real)
grep -cE '^(DROP TABLE|CREATE TABLE)' backup.sql

# 2. importe num banco descartável, de preferência com usuario sem privilégio
#    na base real, e CONTEE antes de confiar:
mysql -uuser -p outro_banco < backup.sql
SELECT COUNT(*) FROM wp_posts;      # tem que bater com o esperado
```

Se precisar validar dentro da mesma base (sem permissão de `CREATE DATABASE`),
importe em tabelas de **nome diferente** e confira as contagens:

```bash
sed 's/`wp_posts`/`zz_posts_test`/g' restore_wp_posts.sql > /tmp/t.sql
mysql -uuser -p base < /tmp/t.sql      # crie zz_posts_test, não toca em wp_posts
```

E para backups que você pretende **reimportar depois**, gere sem DDL:

```bash
mysqldump ... --no-create-info --skip-triggers --complete-insert
# => so INSERT, sem DROP/CREATE: reimportavel sem risco
```

## 1.10 Restaurar `wp_posts` ressuscita registros apagados depois do dump

O dump de referência é de **2026-09-21**. As remoções da Fase 3 (templates,
opções do ACF) e qualquer `DELETE` feito entre 21/09 e o dia do restore **não
existem no dump**. Ao restaurar `wp_posts`/`wp_postmeta`, tudo que foi apagado
nesse intervalo volta.

Consequência real observada: os 3 posts do ACF (`acf-field` ×2,
`acf-field-group` ×1) foram apagados, e reapareceram quando `wp_posts` foi
restaurada. As 2 options (`acf_version`, `acf_site_health`) **não** voltaram,
porque o dump de options não foi reimportado — ou seja, um estado ficou
parcialmente revertido sem ninguém perceber.

**Regra:** depois de qualquer restore, refaça a lista de "já removido" e
confirme item por item. Trate o restore como um *rollback* do banco, não como uma
restauração pontual.

## 1.11 Nunca edite opção serializada com `REPLACE` no SQL

Para desativar um plugin, o `REPLACE` parece inofensivo e não é:

```sql
-- ERRADO: remove o elemento mas deixa a chave e o cabecalho como estavam
UPDATE wp_options SET option_value = REPLACE(option_value,
       's:30:"advanced-custom-fields/acf.php";', '')
 WHERE option_name='active_plugins';
-- resultado: a:12:{ ... 11 elementos ... }
-- o header continua "a:12" e sobrou a chave "i:0;" orfa.
```

`unserialize()` falha, o WordPress trata como `false` e **desativa todos os
plugins de uma vez** — sem erro visível. Foi o que aconteceu com o
`active_plugins` aqui.

Faça assim:

- **preferível:** tela admin (`/wp-admin/plugins.php`) ou `wp plugin deactivate`;
- **se precisar de SQL:** deserializar, filtrar, reserializar com PHP — nunca
  editar a string. E conferindo o resultado:

```php
$a = unserialize( $option_value );
var_dump( $a === false ? unserialize_last_error_msg() : count($a) );
```

Confira sempre o header: `substr($serialized, 0, 6)` tem que bater com a contagem
real de elementos.

## 1.12 O que é "dado do ACF" e o que não é

Ao remover o ACF, a distinção importa — apagar a coisa errada aqui quebra o admin
sem quebrar o front, que é o pior tipo de bug.

| | O que é | Situação |
|---|---|---|
| **Dado do ACF** | `wp_posts` com `post_type` `acf-field` / `acf-field-group`; options `acf_*` | Removido |
| **Postmeta `_rlm` / `_url_demo`** | 117.029 linhas com valor `field_5e778eac382e1` e `field_5ec01cc14737a` | **Removido em 2026-09-27** |
| **Postmeta `rlm` / `url_demo`** | 116.988 linhas com dado real (`Letra \| Melodia`, `/demos/…mp3`) | **Removido em 2026-09-27** |

**O que mudou em 2026-09-27.** As 4 chaves foram apagadas (234.017 linhas). A
decisão partiu de "preservar", mudou para "remover", e a auditoria anterior à
remoção mostrou que a preocupação original estava mal formulada.

O `_` na frente **não** indica que a linha seja dado do ACF. `_rlm` e `_url_demo`
guardavam `field_5e778eac382e1` e `field_5ec01cc14737a` — **ponteiros para campos
do ACF que não existem mais**, lidos por zero linhas de código. Eram lixo, não
dado. O dado real estava sempre nas duas chaves sem `_`.

As definições de campo estavam **sem `name` e sem `key`** (só `type`,
`instructions`, `required`, …). Sem `key` o ACF não sabe qual postmeta escreve,
e o valor `field_5e…` confirma: o ACF guardou a referência a si mesmo, nunca
chegou a ser lido.

Nada de valor válido foi perdido:

- `url_demo` (81.896) estava integralmente copiado em `_centralmidi_demo_audio`
  (81.852, todos preenchidos, **0 produtos** com demo no legado e vazio na nova);
- `rlm` (35.092) perdeu só 2 valores fora da whitelist de `map_classificacao`
  (`Lendas`, e `Lertra`, que é erro de digitação) — os dois já estavam registrados
  como conflito `rlm_desconhecido`.

O site público nunca leu essas chaves: o player usa
`CentralMidi_DB::get_product_demo_url()`, que lia `_centralmidi_demo_audio`.

**O que sobrou disso** (`PENDENCIAS.md` item 1):

1. `class-centralmidi-migration.php` só lê `rlm` e `url_demo` para popular
   `classificacao` e `demo_audio`. **Reexecutar a migração agora zera os dois
   campos em 81.852 produtos.** Isto tirou a rede de segurança do staging.
   *(Mitigado: a classe não registra nenhum hook, por decisão documentada no
   próprio código — `class-centralmidi-migration.php:64-73`.)*
2. `functions.php` chamava `update_field()`, que deixou de existir junto com o
   ACF. Salvar RLM em `/ferramentas-admin/` dava **erro fatal** — bug anterior a
   esta remoção. *(Corrigido: não há mais nenhuma chamada a
   `get_field`/`update_field` no tema ou no plugin, e os cinco endpoints
   `pa_admin_*` foram reescritos para `wp_centralmidi_*`.)*

**Antes de recriar qualquer campo:** procurar `get_field` / `update_field` no
template e nos plugins. Se aparecer, o ACF ainda é dependência viva.

```bash
docker exec local_wp sh -c \
  "grep -rnE 'get_field|update_field|the_field|has_field|acf_' \
   /var/www/html/wp-content/themes/flatsome-child/ \
   /var/www/html/wp-content/plugins/centralmidi/ 2>/dev/null | grep -v '\.min\.'"
```

Item de prevenção, não de correção: usar `get_post_meta()` antes de criar campo
novo, em vez de reintroduzir o ACF.

## 1.13 A árvore `product_cat` legada é "letra → artista", e o WooCommerce sobe ela

Os termos de letra (A, B, C... e o bucket) são `product_cat` reais, com `count=0`
desde que a migração esvaziou as taxonomias. Cada artista é filho da sua letra.
O WooCommerce monta o breadcrumb subindo essa árvore e, como a base de
`product_cat` é `/artistas/`, saíam URLs com a letra no meio:

```
Início / P / PANDA E MARIANA FAGUNDES
               -> /artistas/p/                          (200, mas é a página da letra do A-Z)
               -> /artistas/p/panda-e-mariana-fagundes/ (404)
```

Note a ambiguidade: `/artistas/p/` responde 200 **por acaso**, porque colide com
a página da letra do A-Z. Parecia funcionar e não era o link do artista.

Hoje `cmidi_breadcrumb_artista_busca()` reescreve para `Início / Artistas /
ARTISTA`, com o artista apontando para `/?s=ARTISTA` — o mesmo destino do
diretório A-Z. O bloco "Categoria:" do `single-product/meta.php` tem o mesmo
defeito e é tratado por `cmidi_term_links_artista_busca()`.

Quatro detalhes que custaram tempo aqui:

**1. `WC_Breadcrumb` guarda pares numéricos, não associative.**
`add_crumb( $name, $link )` grava `array( $name, $link )`, e o template do
Flatsome lê `$crumb[0]`/`$crumb[1]`. Código que espera `$crumb['label']` e
`$crumb['URL']` recebe string vazia em silêncio — sem erro, sem log, e o filtro
"não funciona" sem explicar por quê.

**2. O gancho é `term_links-{$taxonomy}`, não `get_the_term_list`.**
`get_the_term_list()` monta os `<a>` num **array** e filtra com
`term_links-product_cat`, passando só o array. Ele já vem escopado na taxonomia,
o que é mais seguro do que checar o slug dentro de um HTML montado.

**3. Identifique o termo pela URL, nunca pelo nome.**
Existe um artista chamado **"V"** (termo `208222`) dentro da letra **"V"**
(`208219`). Os nomes são idênticos; as URLs não (`/artistas/v/` contra
`/artistas/v/v-v/`). Comparando rótulo, o crumb do artista não era reconhecido, o
guard de segurança caía para "devolve a trilha intacta" e o link morto ficava na
tela. Ache comparando com `get_term_link()`.

**4. `woocommerce_breadcrumb` não é chamado onde você imagina.**
O Flatsome reengancha em `flatsome_breadcrumb`
(`inc/woocommerce/structure-wc-global.php:142`) em vez do gancho padrão do
WooCommerce, e as integrações de SEO (SEOPress, Yoast, Rank Math, AIOSEO)
**removem** esse callback e colocam o seu no lugar
(`inc/integrations/wp-seopress/class-wp-seopress.php:42`). Se o breadcrumb não
passa pelo seu filtro, verifique `get_theme_mod('wpseopress_breadcrumb')` antes
de culpar o seu código.

Para depurar um filtro de breadcrumb: `error_log()` do PHP **não** respeita
`WP_DEBUG_LOG` — ele vai para o log do php-fpm. Grave em arquivo
(`file_put_contents('/tmp/x.txt', ..., FILE_APPEND)`) e limpe depois.

E cuidado com o teste em linha de comando: `is_singular('product')` é `false` no
CLI, então um filtro que começa com esse guard parece não funcionar mesmo quando
está correto.

---

# 2. Itens do checklist final que continuam abertos

Conferidos em 2026-09-28 contra o estado atual do ambiente.

## 1.14 Home: seções inferiores migradas para o child (2026-09-28)

Migradas as 4 seções da home que vinham **depois** da lista de lançamentos
(`get_featured`): **Bem-vindos a Central MIDI!**, **Sobre os nossos serviços**,
**Nossa equipe está preparada para lhe atender!** e **Leis de Copyright**.

**O que era:** blocos de shortcode do UX Builder no `post_content` da home
(page 14) — `[section]`, `[row]`, `[col]`, `[title]`, `[button]`, `[scroll_to]`,
`[ux_image]`, `[contact-form-7]`. O Flatsome renderiza esses shortcodes com
classes de tema (`section`, `row/col`, `section-title`, `img has-hover`,
`bullet-checkmark`, `button`) e IDs aleatórios a cada request
(`section_*`, `banner-*`, `text-box-*`).

**O que virou:** HTML estático próprio no próprio `post_content`, com classes
`cm-home-*` (`cm-home-about`, `cm-home-services`, `cm-home-team`,
`cm-home-legal`) e CSS novo no fim de `style.css` (bloco "HOME - SECOES PROPIAS").
Sem nenhuma classe de tema no corpo dessas seções — `bullet-checkmark` agora é 0
na página.

**Armadilha nova (difícil de notar):** `mariadb -N -e "SELECT post_content"`
por padrão **escapa** newlines (`\n` vira `\n` literal) e aspas ao imprimir —
o dump fica maior que o valor real e o MD5 não bate com o banco. Para editar
conteúdo de post é obrigatório usar `--raw --batch -N` (conferir MD5 depois;
o dump "com literal \n" da primeira tentativa tinha até 232 escapes a mais).
Aguardas de integridade via `AND MD5(post_content)='...'` funcionaram; o
`FROM_BASE64()` do MariaDB roda normal, mas o `<` na stdin do `docker exec`
não entregou o SQL (rodou via `docker cp` + leitura dentro do container).

**Verificações:**
- `LENGTH` da coluna: 10 937 → 10 924 bytes; MD5 `225c2b4ed...` → `23c18c52a...`.
- Texto preservado: 22 blocos de texto idênticos entre o render antigo (sec04-07)
  e o novo; única diferença é o par de chaves PIX reunido numa linha
  ("contato@centralmidi.com.br ou pix@centralmidi.net").
- Formulário: mantido `[contact-form-7 id="150924"]` — o child registra esse
  shortcode (`cmidi_cf7_compat_shortcode`) e renderiza o form nativo
  `cm-native-contact-form`; input branco (#fff) e botão submit #00d284.
- `#contato` preservado (âncora `cm-home-anchor`; o rodapé linka `/#contato`).
- Hero (`[ux_slider]` com 9 `[ux_banner]`) e wrapper `[section][get_featured]`
  **não** foram migrados (fora do escopo) — ver PENDENCIAS §2 passo 4.
- Medições (playwright): seções-full 1440px; "Sobre" mantém altura fixa 300px
  (desktop e mobile); card de contato #e3e3e3 raio 5px; QR 29% (~186px); mobile
  empilha na ordem esperada (texto → card; foto → info).

Backup do estado anterior: `backup/home_migracao_secoes/wp_posts_id14_*.sql`.

**Refator de tema (mesmo dia):** as cores do bloco `HOME - SECOES PROPIAS` foram
tokenizadas em `--cm-home-*`, definidas nos dois escopos do sistema de tema
(`:root/[data-theme=dark]` e `[data-theme=light]/body.theme-light`) — nada de hex
solto nas regras. Por decisão de design, lajes `#333b40`, card `#e3e3e3` e o
"Sobre" (overlay branco) são contraste FIXO: valores idênticos nos dois temas
(medição: dark==light em todos os 22 itens). Para variar o claro basta editar o
bloco `[data-theme="light"]`. Achado ao medir: `custom-style.css:11` trava
`html,body { background-color: #080c13 !important }`, então hoje o toggle light
não troca o fundo da página — as seções transparentes continuam sobre fundo
escuro nos dois temas. Melhoria moral embutida: os e-mails PIX saíram do
`#4a4a4a` herdado (quase invisível no escuro) para `--cm-home-pix-emails`
`#e5e9ef` legível.
**Bugfix ligado ao light:** `.cm-track-card` usa `background: var(--cm-bg-card)` → no light o
card fica branco, mas `.cm-btn-buy` (custom-style.css:2939) tinha `color:#f8fafc` + borda
`rgba(255,255,255,.15)` fixas → botão "Comprar" sumia no branco. Corrigido com override só no
light: `[data-theme="light"] .cm-track-card .cm-btn-buy` → `color: var(--cm-text-secondary)`,
`border-color: var(--cm-border-color)` (dark intocado). Player (`#cm-global-player`) continua
escuro fixo e o botão dele (`#00dfa0`) é válido nos dois temas.

## 2.1 Aberto — `/midis/`: catálogo de produtos vs. catálogo de artistas

O checklist original dizia: *"trocar o tema para `central-midi` e criar a página
`[centralmidi_catalogo]`"*.

**Estado atual (verificado 2026-09-28):** `/midis/` **não é mais vazia**.
`class-centralmidi-artistas.php:90` (`handle_template_redirect`) roteia tanto
`/artistas/` quanto `/midis/` para o catálogo A-Z de artistas — a página renderiza
o diretório completo (testado ao vivo: "Artistas com a letra A"). O shortcode
`[centralmidi_catalogo]` continua registrado mas **sem nenhuma página publicada**
(0 ocorrências); `catalog_page_id()` devolve `0` e `catalog_url()` cai no
fallback `home_url('/midis/')`.

**Decisão em aberto:** o que `/midis/` deve servir — o **catálogo de produtos**
(criar a página com `[centralmidi_catalogo]`) ou o **A-Z de artistas** (comportamento
atual)? A grade de produtos hoje renderiza na home, não numa URL dedicada.

## 2.2 Aberto — remover os templates de taxonomia legados

Os quatro continuam no tema:

- `taxonomy-product_cat.php`
- `taxonomy-mes_de_lancamento.php`
- `taxonomy-genero_musical.php`
- `page-ferramentas-admin.php`

## 2.3 Aberto — rotacionar a senha administrativa do WordPress

Item que o checklist final deixou pendente e que segue valendo. Era o item de
maior risco aberto depois da migração.

## 2.5 Concluído desde então (mantido como registro)

- **Remover o plugin ACF do disco** — feito. `wp-content/plugins/advanced-custom-fields` não existe mais.
- **`wp-content/object-cache.php` órfão do LiteSpeed** — feito. O arquivo não existe mais.
- **Corrigir o `update_field()`** — feito. Nenhuma chamada a `get_field`/`update_field`/`the_field`/`has_field`
  restou no tema ou no plugin.
- **Desacoplar o rodapé do tema pai (PENDENCIAS §2, passo 2)** — feito em 2026-09-28.
  `flatsome-child/footer.php` passou a existir (antes o WordPress caía em `flatsome/footer.php`, que abria a
  casca de 12 linhas e disparava `do_action('flatsome_footer')`). O child era o único consumidor desse hook;
  agora zera o uso de hooks do Flatsome por parte do child. O `cmidi_render_modern_footer` foi removido de
  `functions.php` (o `remove_action` defensivo do `flatsome_page_footer` ficou).
  Botão back-to-top reimplementado sem `flatsome_go_to_top()`/`flatsome_html_atts()`/`get_flatsome_icon()`:
  mesmo marcado `#top-link.back-to-top` e mesmas classes (6 regras de CSS do child miram esse seleto: style.css:442,
  :452 e player.css:653, :655, :874, :876), ícone trocado de `fl-icons`/`icon-angle-up` para Remix Icon
  `ri-arrow-up-line` (medida por tinta no canvas: antigo 13x8 a 22px; novo ~13x12 a 18px), e clique virou handler
  nativo em `custom-script.js` (`cmBttBound`, scroll suave respeitando `prefers-reduced-motion`).
  Verificação: HTML do footer idêntico em 8 páginas (5.134 → 5.140 bytes, único diff = aria-label em pt + classe
  do ícone); `/checkout/` sem footer; botão 39x39, right=27, z=9997; clique 3000 → 0. Nenhum plugin usa
  `flatsome_footer`.
  Resíduo intencional: o botão reutiliza classes utilitárias de CSS do Flatsome (`button icon invert plain fixed
  bottom z-1 is-outline circle`), então o visual continua vindo da stylesheet do pai até o passo 5 (remover o tema);
  nesse momento portar o estilo para o style.css do child. A fonte `fl-icons` ainda é carregada (outros ícones de
  UI do Flatsome a usam), mas o rodapé não depende mais dela.
- **Desacoplar o template da página home do tema (PENDENCIAS §2, itens de home)** — feito em 2026-09-28.
  A home (page 14) e `servicos` (page 91) usavam o template `page-blank.php` do tema, que chamava
  `do_action('flatsome_before_page')` e `do_action('flatsome_after_page')`. O child passou a ter `page-blank.php`
  próprio sem esses hooks (mantém `get_header` + `#content`/`the_content` + `get_footer`). Como as duas páginas não
  têm excerpt, senha, paginação nem comentários abertos, nenhum listener dos hooks emitia nada — HTML renderizado
  idêntico (diferenças no diff são só os IDs aleatórios `banner-*`/`section_*`/`text-box-*` que o builder regenera
  a cada request, e espaço em branco).
  **Ainda dependente do tema nessa página:** o corpo da home é conteúdo misto: as 4 seções
  inferiores (Bem-vindos, Sobre os serviços, Nossa equipe, Leis de Copyright) viraram `cm-home-*`
  próprios do child em 2026-09-28 (ver §1.14); o **hero** continua como shortcode `[ux_slider]`
  com classes de tema (`slider-wrapper`+flickity/parallax) cujo visual vem do `flatsome.css`;
  e o cabeçalho (PENDENCIAS §2 passo 1).
- **Logo do rodapé maior (2026-09-28):** `.cm-footer-logo-img` passou de `max-width:180px` para
  `width:240px; max-width:100%`. Armadilha: só `max-width` não cresce — o `<img>` tem atributo
  `width="180"` no HTML; é preciso `width:240px` explícito (medição 240×62, sem overflow em
  1440 e 390).
- **Itens 1–3 de `PENDENCIAS.md` removidos por decisão (2026-09-28):** os 118 termos `product_cat`
  órfãos, o "Sem categoria" (10 produtos) e o typo `ANDRE LEONNO` (id=1729) serão arrumados
  **manualmente** no cadastro do catálogo — saíram da lista de pendências como itens abertos.
  Conferência ao vivo antes da remoção: "Sem categoria" ainda aparecia em `/artistas/s/` e
  `ANDRE LEONNO` ainda existia na tabela.
- **Bucket de dígito do A-Z (antigo item 4) — verificado e removido de `PENDENCIAS.md`:**
  `/artistas/outros/` listava os 33 esperados, incluindo os 4 artistas que o item mandava
  conferir (`10 CC`, `100 %`, `14 BIS`, `10000 MANIACS`).
- **Reindex do WooCommerce (antigo §2.4) removido:** a tabela `wp_wc_product_lookup` não existe
  no banco; as contagens vêm de taxonomia de um site em operação — o item não se aplica mais.
- **Ambiente `new_centralmidi` descontinuado e repo GitHub limpo (2026-09-29):** o diretório
  `new_centralmidi/` era um ambiente WordPress separado (docker stack 8080/8081, banco
  `centralmidi_db`) com um tema rascunho standalone `central-midi/` e o plugin num commit antigo.
  A stack estava parada (~6 semanas) e o código de lá era a linha **pré-migração** (plugin 1.2.3
  sem `class-centralmidi-artistas.php`/`artistas.js`, A-Z ainda no tema). O desenvolvimento real
  acontece em `atual/` (espelho de produção, stack 8090, plugin **1.2.4**). Ação tomada:
  - Commit de limpeza no GitHub `lucascampos42/centralmidi` (`fdabeb6`, push em 2026-09-29):
    a árvore do `main` passou a conter **somente** `wp-content/plugins/centralmidi/` 1.2.4 +
    `CENTRALMIDI-PLUGIN.md` + `.gitignore`. Removidos da árvore: `docker/`, `docker-compose.yml`,
    `AGENTS.md` (dev creds), tema `central-midi/` e o plugin antigo. O bump 1.2.3→1.2.4 (que nunca
    tinha sido commitado) entrou junto.
  - **Armadilha mantida por escolha:** o histórico do GitHub preserva os commits antigos — as
    senhas de dev (docker) continuam nos commits passados do default branch, só somem da árvore
    atual. Se um dia quiser apagá-las de vez, é preciso reescrever o histórico (force-push) — ver
    passo 5 abaixo.
  - O diretório local **foi mantido** em `new_centralmidi/` (clone com o `.git`, já sincronizado
    com o remote). O GitHub reporta 198 vulnerabilidades de dependências no
    default branch — são de commits passados (tree atual só tem 2 vendored do Tabulator); revisar
    se quiser zerar o alerta.
