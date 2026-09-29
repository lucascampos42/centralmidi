# Central MIDI — Plugin

Plugin WordPress para catálogo de MIDIs integrado ao WooCommerce, com
classificação `#M` / `#L` / `#RLM`.

- **Versão:** 1.2.4 (`centralmidi.php`, `CENTRALMIDI_VERSION`)
- **Requer:** WooCommerce (`Requires Plugins: woocommerce`)
- **Text domain:** `centralmidi`

> Este documento descreve o plugin **como está** em `atual/`. Foi reescrito em
> 2026-09-28 a partir do código e do banco; a versão anterior estava defasada e
> descrevia o plugin de outro projeto (tema `central-midi`, banco
> `centralmidi_db`, porta 8080 — nada disso existe mais aqui).

## Localização

| Peça | Caminho |
|---|---|
| Plugin | `wp-content/plugins/centralmidi/` |
| Tema | `wp-content/themes/flatsome-child/` (child theme do Flatsome) |

O tema `central-midi` **não existe mais**. O tema ativo é o child theme do
Flatsome, que consome o plugin via `CentralMidi_DB`.

## Fonte da verdade: a tabela, não o post meta

Este é o ponto mais importante do plugin atual.

Toda a classificação/artista/gênero/mês/ano de um MIDI vive na tabela
`wp_centralmidi_midis`. **Nada mais grava as chaves `_centralmidi_*` em
`wp_postmeta`** — o que restou ali é resíduo, removível.

- Estado verificado no banco local: **0 linhas** de `_centralmidi_*`.
- `CentralMidi_Admin::upsert_product_meta()` é um nome enganoso: apesar do
  nome, ele chama `CentralMidi_DB::upsert()` e escreve **só na tabela**.
- O metabox do produto, a edição inline e o bulk da tela de MIDIs grava tudo
  via `CentralMidi_DB::upsert()`.
- `CentralMidi_DB::maybe_upgrade()` faz backfill de `demo_audio` da coluna para
  a tabela lendo `_centralmidi_demo_audio` — é o último resto de uso do post
  meta, e só em instalações antigas.

Se for mexer no tema, **não** procure `_centralmidi_artista` esperando achar
chave de banco: use a API estática de `CentralMidi_DB`.

## Classificação

| Código | Significado |
|--------|-------------|
| `M`   | MIDI somente com Melodia |
| `L`   | MIDI somente com Letra sincronizada |
| `RLM` | MIDI com Melodia e Letra sincronizada |

`sanitize_classificacao()` normaliza para maiúsculas e aceita `M`, `L`, `RLM`.
Vazio ou `NONE` viram `''` (sem classificação) — **não** é erro.

Distribuição real no banco local (81.852 linhas):

| Valor | Linhas |
|---|---:|
| *(vazio)* | 47.047 |
| `RLM` | 16.590 |
| `L` | 10.506 |
| `M` | 7.709 |

Mais da metade do catálogo está **sem classificação**.

## Tabelas do banco

Criadas em `register_activation_hook` (`CentralMidi_DB::create_table()`,
`create_artistas_table()`, `create_generos_table()`). `centralmidi_uninstall()`
faz drop. `maybe_upgrade()` roda a cada load (`plugins_loaded`) e cria colunas
faltantes.

### `wp_centralmidi_midis`

| Coluna           | Tipo              | Null | Default | Descrição |
|------------------|-------------------|------|---------|-----------|
| `id`             | bigint unsigned   | não  | AI      | PK |
| `product_id`     | bigint unsigned   | não  | —       | ID do produto WooCommerce, **UNIQUE** |
| `artista_id`     | bigint unsigned   | sim  | `NULL`  | FK `wp_centralmidi_artistas.id` |
| `genero_id`      | bigint unsigned   | sim  | `NULL`  | FK `wp_centralmidi_generos.id` |
| `mes_lancamento` | tinyint unsigned  | não  | `0`     | 1–12 |
| `ano_lancamento` | smallint unsigned | não  | `0`     | ex.: 2026 |
| `classificacao`  | varchar(3)        | não  | `'M'`   | `M` / `L` / `RLM` / `''` |
| `publicado`      | tinyint(1)        | não  | `1`     | `1` disponível, `0` em breve |
| `demo_audio`     | varchar(255)      | não  | `''`    | URL ou **nome do arquivo** do MP3 |
| `created_at`     | datetime          | não  | —       | |
| `updated_at`     | datetime          | não  | —       | |

Índices: `PRIMARY (id)`, **UNIQUE `product_id`**, e chaves simples em
`artista_id`, `genero_id`, `mes_lancamento`, `ano_lancamento`,
`classificacao`, `publicado`.

Não há mais colunas denormalizadas `artista`/`genero`: `maybe_upgrade()` as
dropa depois da migração de strings legadas → IDs.

### Tabelas de referência

`wp_centralmidi_artistas`: `id`, `nome` (UNIQUE), **`foto_id`**, `created_at`,
`updated_at`.

`wp_centralmidi_generos`: `id`, `nome` (UNIQUE), `created_at`, `updated_at` —
**sem** `foto_id`.

Contagens no banco local: **81.852** MIDIs, **13.625** artistas, **25** gêneros.
Nenhum MIDI com `publicado=0` no momento.

### `publicado`

Filtra os MIDIs dentro das consultas do próprio plugin: home, catálogo,
diretório de artistas, busca, busca ao vivo. `publicado=0` é omitido de todas
elas (a condição aparece como `AND publicado = 1` nos métodos de
`class-centralmidi-db.php`).

**Limitação conhecida:** não existe filtro `woocommerce_is_purchasable` no
plugin nem no tema. O bloqueio de compra descrito em versões anteriores deste
documento **não está implementado**. Todos os produtos locais estão com
`post_status = publish`, então `publicado` só é garantido nas telas que passam
pelo plugin.

## Demo de áudio

`demo_audio` é **coluna da tabela**, não post meta. A API relevante:

- `CentralMidi_DB::set_demo_audio($product_id, $value)` — atualiza só essa coluna.
- `resolve_media_url($value, $mes, $ano)` — resolve nome de arquivo para
  `/midis/<ano><mes>/arquivo.mp3`.
- `get_product_demo_url($product_id)` — URL final da demo.
- `apply_demo_filter($mode, $search, &$join, &$where)` — filtro de busca por demo.
- `count_products_with_demo()`, `get_duplicated_demo_urls()` — auditoria.

O metabox aceita upload (grava em `midis/<ano><mes>/`) ou URL/nome manual.

## Shortcode `[centralmidi_catalogo]`

Registrado via `add_shortcode` em `class-centralmidi-catalog.php`, com filtros
por artista, gênero, mês e classificação (GET), paginação
(`por_pagina`, padrão 12) e cards com `template-parts/card-midi.php`.

**Está registrado mas não é usado em nenhuma página publicada.** Verificado no
banco: nenhuma página contém o shortcode. E `CentralMidi_DB::catalog_page_id()`
procura justamente uma página com o shortcode, então retorna `0` e
`catalog_url()` cai no fallback `home_url('/midis/')`.

Consequência prática: nada renderiza. `enqueue_assets()` apenas faz
`wp_register_style('centralmidi-catalog')`; o `wp_enqueue_style()` de verdade
está dentro do callback do shortcode (`render()`). Como o shortcode nunca roda,
o `catalog.css` **não chega a ser carregado** — fica registrado e órfão.

## Admin

Menu de topo **Central MIDI** (ícone `dashicons-format-audio`, posição 56,
capacidade `manage_options`) com três submenus:

| Página | Slug | Render |
|---|---|---|
| MIDIs | `centralmidi-midis` | `render_midis_page()` |
| Artistas | `centralmidi-artistas` | `render_artistas_page()` |
| Gêneros | `centralmidi-generos` | `render_generos_page()` |

### Tabela de MIDIs (Tabulator 6.5.2)

Vendor local em `assets/vendor/tabulator/` (`tabulator.min.js` / `.css`) — sem CDN.

- Dados server-side via `wp_ajax_centralmidi_midis_table`: paginação remota
  (20/50/100), ordenação e filtros de cabeçalho. Só a página atual trafega.
- Edição inline via `wp_ajax_centralmidi_midis_save`: selects para
  artista/gênero/classificação, número para mês/ano.
- Bulk via `wp_ajax_centralmidi_midis_bulk`: definir artista, gênero, mês, ano ou
  classificação; publicar/despublicar; limpar metadados.
- Botões "Selecionar página", "Limpar seleção" e "Exportar CSV".
- Todos exigem `manage_options` + nonce, gravam via `CentralMidi_DB::upsert()` e
  chamam `clear_home_cache()`.

### Endpoints AJAX registrados

| Action | Handler |
|---|---|
| `centralmidi_midis_table` | `handle_midis_table_ajax()` |
| `centralmidi_midis_save` | `handle_midis_save_ajax()` |
| `centralmidi_midis_bulk` | `handle_midis_bulk_ajax()` |
| `centralmidi_scan_folder` | `handle_scan_folder_ajax()` |
| `centralmidi_process_batch_chunk` | `handle_process_batch_chunk_ajax()` |

Os dois últimos implementam a importação em lote por scan de pasta + chunks.
**Não existe página `/importar-midis/`** — o fluxo é dirigido pelo admin, não
por uma página do site. A importação cria MIDIs com `publicado=0` por padrão.

## Classes

| Arquivo | Linhas | Papel |
|---|---:|---|
| `centralmidi.php` | 66 | Bootstrap, constantes, activate/uninstall |
| `includes/class-centralmidi-db.php` | 1.438 | Schema, upsert, consultas, referências, cache |
| `includes/class-centralmidi-admin.php` | 1.226 | Menu, metabox, tela MIDIs, AJAX, importador |
| `includes/class-centralmidi-migration.php` | 809 | Rotina de migração **desregistrada** |
| `includes/class-centralmidi-frontend.php` | 242 | Helpers estáticos **órfãos** |
| `includes/class-centralmidi-catalog.php` | 148 | Shortcode, filtros, `render_card()` |

### `CentralMidi_Migration` — desregistrada de propósito

`__construct()` é vazio e o comentário no código é explícito: o menu
`centralmidi-migracao` e os quatro `wp_ajax_*` da migração foram
**deliberadamente deixados sem registro**. A migração terminou em 2026-09-27 e
o meta legado (`url_demo`, `rlm`) foi purgado; reexecutar agora encontraria
zero chaves legadas, apagaria `_centralmidi_*` em massa, e o handler de reset
droparia as tabelas novas. Os métodos continuam no arquivo caso o catálogo
precise ser reconstruído.

Não_registered = a classe é instanciada em `centralmidi_init()`, mas não faz
nada. Não mexa aqui achando que o fluxo está ativo.

### `CentralMidi_Frontend` — órfã

Só expõe métodos estáticos (`get()`, `get_many()`, `classificacao_label()`,
`mes_label()`, `mes_label_nav()`, `flush()`). Carregada no bootstrap, mas
**nada no projeto a chama** — o tema usa `CentralMidi_DB` diretamente. Os únicos
`CentralMidi_Frontend` no restante do código são menções em comentários.
Código morto candidato a remoção.

## Arquivos do plugin

```
wp-content/plugins/centralmidi/
├── centralmidi.php                      # Bootstrap + hooks de ativação/uninstall
├── includes/
│   ├── class-centralmidi-db.php          # Tabelas, upsert, consultas, referências
│   ├── class-centralmidi-admin.php       # Menu, metabox, tela MIDIs, AJAX, importador
│   ├── class-centralmidi-migration.php   # Migração legada (DESREGISTRADA)
│   ├── class-centralmidi-frontend.php    # Helpers estáticos (ÓRFÃ)
│   └── class-centralmidi-catalog.php     # Shortcode, filtros, cards
└── assets/
    ├── css/catalog.css                   # Catálogo público
    ├── css/admin-midis.css               # Ajustes do Tabulator
    ├── css/admin-migration.css           # Tela de migração (inativa)
    ├── js/midis-table.js                 # Tabela + bulk + inline edit
    ├── js/admin.js                       # CRUD de artistas/gêneros
    ├── js/migration.js                   # Tela de migração (inativa)
    └── vendor/tabulator/                 # Tabulator 6.5.2, local
```

## Integração com o tema

O plugin **não** carrega o card: `CentralMidi_Catalog::render_card()` faz
`get_template_part('template-parts/card-midi', ...)`. O card é do tema.

O tema consome o plugin por `CentralMidi_DB` (em `functions.php`,
`inc/cmidi-artistas.php`, `page-artistas.php`, `page-ferramentas-admin.php` e
os templates de taxonomia), com guarda `class_exists('CentralMidi_DB')`.

Funções do tema que escrevem na tabela: `cmidi_sync_tabela_classificacao()`,
`cmidi_sync_tabela_colunas()`, `cmidi_sync_artista_meta()`,
`cmidi_sync_genero_meta()`, `cmidi_sync_mes_meta()`.

## URLs

O catálogo **não** está em `/midis/`.

| URL | Realidade |
|---|---|
| `/` | Homepage — é aqui que a grade de produtos renderiza (72 `cm-track-card` na home local) |
| `/midis/` | Página ID 9, **vazia** — sem shortcode, sem produtos |
| `/midis-por-genero/` | Arquivo de `genero_musical` (25 termos) |
| `/midis-por-mes-de-lancamento/` | Arquivo de `mes_de_lancamento` (13 termos) |
| `product_cat` | Artistas — **13.662** termos |
| `/ferramentas-admin/` | Página ID 1219186 (admin do tema, fora do plugin) |

`/loja/` e `/shop/` retornam **404** — não há página de shop WooCommerce criada.
Existe `taxonomy-product_cat.php` no tema, então os artistas são servidos por
`product_cat`.

## Ambiente local

O ambiente deste projeto **não** é o do `new_centralmidi/`.

| Serviço | Container | Endereço |
|---|---|---|
| WordPress | `local_wp` | `http://127.0.0.1:8090` |
| MariaDB | `local_db` | `3306` (interno) |
| phpMyAdmin | `local_pma` | `http://127.0.0.1:8091` |

Banco local: `local_midi`, prefixo `wp_` (credenciais no `docker-compose.yml`
de `atual/`, fora do repo). A home redireciona `127.0.0.1` → `localhost`,
então use `http://localhost:8090`.

`wp-cli` não faz parte da imagem oficial do WordPress e some ao recriar o
container — prefira SQL direto (`docker exec local_db mysql …`).

## Pendências conhecidas

1. **`/midis/` está vazia** e o shortcode não está em página nenhuma. Se a
   intenção era usar o shortcode, ele precisa ser inserido — hoje
   `catalog_page_id()` retorna `0`.
2. **`CentralMidi_Frontend` é código morto** (242 linhas).
3. **`publicado=0` não bloqueia compra** — falta o filtro
   `woocommerce_is_purchasable`.
4. **47.047 MIDIs sem classificação** (56% do catálogo).
5. **`/loja/` e `/shop/` dão 404** — não há página de shop.
6. **Assets da migração** (`admin-migration.css`, `migration.js`) são carregados
   por código de tela que não registra mais nada.
