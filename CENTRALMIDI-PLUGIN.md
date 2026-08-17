# Central MIDI — Plugin

Plugin WordPress para catálogo de MIDIs integrado ao WooCommerce, com classificação `#M`/`#L`/`#RLM`.

## Localização

- Plugin: `wp-content/plugins/centralmidi/`
- Tema (custom): `wp-content/themes/central-midi/`

## Recursos

- **Tabela própria no banco** `wp_centralmidi_midis`
- **Metabox** no produto WooCommerce para metadados do catálogo
- **Shortcode** `[centralmidi_catalogo]` com filtros e grid público
- **Badges de classificação** nos cards

## Classificação

| Código | Significado |
|--------|-------------|
| `#M`   | MIDI somente com Melodia |
| `#L`   | MIDI somente com Letra sincronizada |
| `#RLM` | MIDI com Melodia e Letra sincronizada |

## Tabelas do banco

### `wp_centralmidi_midis`

Colunas:

| Coluna           | Tipo              | Descrição                             |
|------------------|-------------------|---------------------------------------|
| `id`             | BIGINT UNSIGNED   | PK auto increment                     |
| `product_id`     | BIGINT UNSIGNED   | ID do produto WooCommerce (único)     |
| `artista_id`     | BIGINT UNSIGNED   | FK para `wp_centralmidi_artistas.id`  |
| `genero_id`      | BIGINT UNSIGNED   | FK para `wp_centralmidi_generos.id`   |
| `mes_lancamento` | TINYINT UNSIGNED  | Mês de lançamento no site (1–12)      |
| `ano_lancamento` | SMALLINT UNSIGNED | Ano de lançamento (ex.: 2026)         |
| `classificacao`  | VARCHAR(3)        | `M`, `L` ou `RLM` (default `M`)       |
| `publicado`      | TINYINT(1)        | `1` disponível p/ venda (default), `0` em breve — oculto do público e não comprável |
| `created_at`     | DATETIME          | Data de criação                       |
| `updated_at`     | DATETIME          | Data de atualização                   |

Índices: `UNIQUE (product_id)`, `KEY (mes_lancamento)`, `KEY (classificacao)`, `KEY (publicado)`.

A tabela é criada na ativação do plugin (`register_activation_hook`) e removida no uninstall. `maybe_upgrade()` adiciona colunas novas (ex.: `publicado`) em instalações existentes, com backfill default `1`.

### Tabelas de referência

`wp_centralmidi_artistas` (com coluna extra `foto_id`) e `wp_centralmidi_generos`:

| Coluna       | Tipo            | Descrição              |
|--------------|-----------------|------------------------|
| `id`         | BIGINT UNSIGNED | PK auto increment      |
| `nome`       | VARCHAR(255)    | Nome (UNIQUE)          |
| `created_at` | DATETIME        | Data de criação        |
| `updated_at` | DATETIME        | Data de atualização    |

A migração de strings legadas (`artista`, `genero`) → IDs é feita em `CentralMidi_DB::maybe_upgrade()`, que roda a cada load e, após migrar, dropa as colunas antigas.

## Metadados por produto (post meta)

| Meta key                       | Descrição                  |
|--------------------------------|----------------------------|
| `_centralmidi_artista`         | Nome do artista (denormalizado) |
| `_centralmidi_artista_id`      | FK `wp_centralmidi_artistas.id` |
| `_centralmidi_genero`          | Nome do gênero (denormalizado) |
| `_centralmidi_genero_id`       | FK `wp_centralmidi_generos.id` |
| `_centralmidi_mes_lancamento`  | Mês de lançamento (1–12)   |
| `_centralmidi_ano_lancamento`  | Ano de lançamento          |
| `_centralmidi_classificacao`   | `M`, `L` ou `RLM`          |
| `_centralmidi_publicado`       | `1` disponível p/ venda (default) / `0` em breve |
| `_centralmidi_demo_audio`      | URL do MP3 de demonstração |
| `_centralmidi_file_url`        | Link do arquivo MIDI já enviado via FTP (padrão `dominio/midis/<mês>/<ano>/<arquivo>.mid`) |

Ao salvar o produto, os valores são persistidos como post meta e sincronizados (`upsert`) na tabela `wp_centralmidi_midis`.

## Shortcode `[centralmidi_catalogo]`

- Filtros por **artista**, **gênero**, **mês de lançamento** e **classificação** (via GET)
- Lista produtos WooCommerce publicados
- Paginação
- Cards com capa, artista, título, gênero, mês, classificação (`#M`/`#L`/`#RLM`) e preço
- Atributos: `por_pagina` (padrão `12`)

Exemplo de uso:

```
[centralmidi_catalogo]
[centralmidi_catalogo por_pagina="24"]
```

## Admin — listagem interativa e edição em lote

Página **Central MIDI › MIDIs** (`centralmidi-midis`) usa **Tabulator 6.5.2** (vendor local em `assets/vendor/tabulator/`):

- Tabela com **dados server-side** (`wp_ajax_centralmidi_midis_table`): paginação remota (20/50/100), ordenação e filtros de cabeçalho (produto, artista, gênero, mês, ano, classificação) — só a página atual trafega.
- **Edição inline** de células (`wp_ajax_centralmidi_midis_save`): selects para artista/gênero/classificação, número para mês/ano e input URL para o link do arquivo.
- **Edição em lote** (`wp_ajax_centralmidi_midis_bulk`): definir artista, gênero, mês, ano ou classificação, **remover link do arquivo** (`clear_file`) ou remover os metadados MIDI.
- Botões "Selecionar página", "Limpar seleção" e **"Exportar CSV"**.
- Coluna **Arquivo** exibe o link do MIDI salvo no servidor (vazio = "Sem arquivo — clique para definir").
- Todos os endpoints exigem `manage_options` + nonce `centralmidi_ajax`; operações atualizam post meta (`_centralmidi_*`) e sincronizam via `CentralMidi_DB::upsert()`; `clear_home_cache()` ao final.

## Arquivos do plugin

```
wp-content/plugins/centralmidi/
├── centralmidi.php                         # Bootstrap + hooks de ativação/uninstall
├── includes/
│   ├── class-centralmidi-db.php            # Tabela, upsert, distinct, busca de IDs
│   ├── class-centralmidi-admin.php         # Metabox, página MIDIs (Tabulator) + endpoints AJAX
│   └── class-centralmidi-catalog.php       # Shortcode, filtros e render dos cards
└── assets/
    ├── css/catalog.css                     # Estilos do catálogo público
    ├── css/admin-midis.css                 # Ajustes do Tabulator no admin
    ├── js/midis-table.js                   # Tabela interativa (Tabulator) + bulk + inline edit
    └── vendor/tabulator/                   # Tabulator 6.5.2 (tabulator.min.js / .css) — local, sem CDN
```

## Ajustes extras realizados

- **`page.php` no tema** `central-midi` — não existia; o `index.php` ignorava o conteúdo das páginas, então foi criado para renderizar o conteúdo (ex.: página com o shortcode).
- **Apache `AllowOverride All`** — via `docker/wordpress-apache.conf` montado em `/etc/apache2/conf-enabled/zz-centralmidi.conf` no `docker-compose.yml`.
- **`.htaccess` com regras de rewrite** — para URLs limpas (`/midis/`).
- Página canônica do catálogo: **`/midis/`** (ID 22) com o shortcode.

## Ambiente

- `docker-compose.yml`: WordPress (`:8080`), MariaDB, phpMyAdmin (`:8081`)
- URL do catálogo: `http://localhost:8080/midis/`
- URL do site: `http://localhost:8080/`

## Nota

O `wp-cli` não fazia parte da imagem oficial do WordPress (desaparece ao recriar o container). Para usá-lo de forma persistente, instalar no Dockerfile ou usar `docker compose exec`.