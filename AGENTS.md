# AGENTS.md — Central MIDI

Projeto: loja WordPress + WooCommerce de arquivos MIDI, com plugin próprio de catálogo e tema customizado em dark mode (com opção de tema claro seguindo o dispositivo).

## Stack e ambiente

- Docker Compose com 3 serviços (ver `docker-compose.yml`):
  - **WordPress** (`centralmidi_wp`) em `http://localhost:8080/`
  - **MariaDB 10.11** (`centralmidi_db`) — banco `centralmidi_db`
  - **phpMyAdmin** (`centralmidi_pma`) em `http://localhost:8081/`
- Credenciais MySQL: user `centralmidi_user` / senha `centralmidi_pass` / root `centralmidi_root_pass`
- Apache como `www-data` mapeado para **uid/gid 1000** (usuário host `lucasc`) via `docker/Dockerfile` — `wp-content` é editável direto do host (bind mount `./wp-content`).
- Permalinks: `/%postname%/`. `.htaccess` com regras de rewrite + `docker/wordpress-apache.conf` (AllowOverride All) montado em `/etc/apache2/conf-enabled/zz-centralmidi.conf`.
- WordPress está configurado com prefixo `wp_`, memória 256M/512M, `FS_METHOD=direct`.

## Comandos úteis (workflow)

```bash
# Lint de PHP (php-cli NÃO existe no host; usar o container)
docker exec centralmidi_wp php -l /var/www/html/wp-content/themes/central-midi/functions.php
docker exec centralmidi_wp sh -c 'for f in /var/www/html/wp-content/themes/central-midi/*.php /var/www/html/wp-content/themes/central-midi/template-parts/*.php; do php -l "$f"; done'

# MySQL direto
docker exec centralmidi_db mysql -u centralmidi_user -pcentralmidi_pass centralmidi_db -e "SELECT ..."

# Checagem de páginas (status + warnings/notices PHP)
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:8080/midis/"
curl -s "http://localhost:8080/midis/" | grep -oE 'Warning:|Notice:|Fatal error|Deprecated:' | sort -u

# Lint de JS/CSS (node existe no host)
node -e "new Function(require('fs').readFileSync('wp-content/themes/central-midi/assets/js/player.js','utf8')); console.log('OK')"
```

**Atenção**: `wp-cli` **não** faz parte da imagem do WordPress — desaparece ao recriar o container. Não usar; prefira SQL direto ou `docker compose exec`.

## URLs e conteúdo

| URL | Descrição |
|-----|-----------|
| `/` | Homepage custom (`index.php`): carousel + lançamentos dos 3 meses |
| `/midis/` | Catálogo público (shortcode `[centralmidi_catalogo]`) — **página canônica** (ID 22) |
| `/artistas/` | Diretório A-Z de artistas (`page-artistas.php`) |
| `/servicos/` | Página de serviços/ordens (`page-servicos.php`) |
| `/loja/` | Shop WooCommerce (archive) |
| `/carrinho/`, `/finalizar-compra/`, `/minha-conta/` | Páginas WooCommerce padrão |
| `/produto/{slug}/` | Single product (via `woocommerce.php`) |

Página `catalogo-midi` (ID 15) foi **movida para a lixeira** (duplicata de `/midis/`). Não recriar.

## Dados de exemplo

- Produtos: `12` Evidências (Playback Multitrack), `13` Tempo Perdido (Arranjo Teclado), `14` Bohemian Rhapsody (Full Arrangement). Demo audio via SoundHelix.
- Artistas (`wp_centralmidi_artistas`): `1` Chitãozinho & Xororó, `2` Legião Urbana, `3` Queen.
- Slides do carousel (`cm_slide`): IDs `17`, `19`, `21`.

## Estrutura do tema (`wp-content/themes/central-midi/`)

Versão `1.1.0` (`style.css`). Todas as páginas passam por `header.php`/`footer.php` com `<main id="cm-main">`.

- `functions.php` — setup (title-tag, thumbnails, woocommerce), enqueues (assets com versão dinâmica via `wp_get_theme()->get('Version')`), CPT `cm_slide` + metabox, `centralmidi_get_slides()` (cache transient), Customizer (`centralmidi_options`: whatsapp/email/pix), demo audio no single product, resource hints.
- `index.php` — home com carousel + 3 meses (dados cacheados em transient `centralmidi_home_<meses>`, 6h).
- `header.php` — logo, busca (nativa `?s=`), nav, **toggle de tema** (`#cm-theme-toggle`), **menu mobile** (`#cm-menu-toggle` / `#cm-primary-nav`), skip-link.
- `footer.php` — rodapé + **player global fixo** (`#cm-global-player`, `#cm-audio-element`), com botão de compra `#cm-player-buy-link`.
- `page-artistas.php`, `page-servicos.php`, `page.php`, `search.php`, `woocommerce.php` — templates.
- `template-parts/card-midi.php` — **card reutilizável** (usa `CentralMidi_DB`, guardado com `class_exists`). Chamado com `get_template_part('template-parts/card-midi', null, array('product_id' => $pid))`.
- `template-parts/carousel.php` — carousel de slides com fallback para hero estático.
- `assets/css/main.css` — **todo o CSS** (~2100 linhas). Variáveis em `:root` (dark default); tema claro via `@media (prefers-color-scheme: light)` + classes `html.cm-theme-light` / `html.cm-theme-dark`; seções WooCommerce.
- `assets/js/` — `player.js` (player global + filtros dropdown), `carousel.js` (só na home), `nav.js` (menu mobile), `theme-toggle.js` (light/dark/system via `localStorage['cm-theme']`).
- `assets/img/` — imagens do design Angular; `assets/images/carousel/` é **legado não usado** (o carousel usa featured image dos slides, tamanho `large`).

### Convenções do tema

- Ícones: **Remixicon 4.3.0** (CDN). Não usar emojis.
- Fontes: Plus Jakarta Sans + JetBrains Mono (Google Fonts, CDN).
- Classes utilitárias: `.cm-btn`, `.cm-btn-primary`, `.cm-btn-outline`, `.cm-badge`, `.cm-container`, `.cm-tracks-grid`, `.cm-visually-hidden`.
- **Não usar estilos inline** em templates; criar classes em `main.css`.
- Sempre escapar saída (`esc_html`, `esc_url`, `esc_attr`, `wp_kses_post` para preço).
- Acessibilidade: skip-link, `aria-label`s, `prefers-reduced-motion` respeitado.

## Estrutura do plugin (`wp-content/plugins/centralmidi/`)

- `centralmidi.php` — bootstrap, constantes, `register_activation_hook` cria tabelas, `centralmidi_init` instancia classes.
- `includes/class-centralmidi-db.php` — tabelas `wp_centralmidi_midis`, `wp_centralmidi_artistas` e `wp_centralmidi_generos`, upsert, `distinct`, `search_product_ids`, `search_by_term`, `get_midis_by_month` (determinístico), `get_artistas_alfabetico`, `clear_home_cache`, `maybe_upgrade` (migração de strings legadas → IDs).
- `includes/class-centralmidi-admin.php` — menu admin "Central MIDI" > MIDIs (**tabela interativa Tabulator 6.5.2**: paginação/sort/filtros server-side, edição inline, bulk via AJAX, export CSV) / Artistas / Gêneros (CRUD genérico via `referencia_config`), metabox no produto, `save_post_product` sincroniza post meta + tabela.
- `includes/class-centralmidi-catalog.php` — shortcode `[centralmidi_catalogo]` (filtros GET, paginação), `render_card()` delega ao template do tema.
- `assets/css/catalog.css` — estilos do catálogo público.

### Metadados por produto

| Meta key | Descrição |
|----------|-----------|
| `_centralmidi_artista` | Nome do artista/banda (denormalizado p/ exibição) |
| `_centralmidi_artista_id` | FK para `wp_centralmidi_artistas.id` |
| `_centralmidi_genero` | Nome do gênero (denormalizado p/ exibição) |
| `_centralmidi_genero_id` | FK para `wp_centralmidi_generos.id` |
| `_centralmidi_mes_lancamento` | Mês (1–12) |
| `_centralmidi_ano_lancamento` | Ano do lançamento (ex.: 2026) |
| `_centralmidi_classificacao` | `M` / `L` / `RLM` |
| `_centralmidi_demo_audio` | URL do MP3 de demonstração |
| `_centralmidi_file_url` | Link do arquivo MIDI já salvo no servidor (padrão `dominio/midis/<mes>/<ano>/<arquivo>.mid`) |

### Tabelas

- `wp_centralmidi_midis`: `product_id` (UNIQUE), `artista_id`, `genero_id`, `mes_lancamento`, `ano_lancamento`, `classificacao`, `created_at`, `updated_at`. **Sem strings denormalizadas** (colunas `artista`/`genero` removidas na migração).
- `wp_centralmidi_artistas`: `id`, `nome` (UNIQUE), `foto_id`, `created_at`, `updated_at`.
- `wp_centralmidi_generos`: `id`, `nome` (UNIQUE), `created_at`, `updated_at`.
- Classificação: `M` (melodia), `L` (letra), `RLM` (melodia + letra).
- `maybe_upgrade()` (roda a cada load) cria tabelas/colunas ausentes e faz a migração idempotente de strings legadas → IDs; após migrar, dropa as colunas `artista`/`genero`.

## Regras/estado importantes

- **WooCommerce fora do modo "coming soon"** — `woocommerce_coming_soon` deve ficar `no` (senão produto/loja mostram página de placeholder).
- **Tema depende do plugin** (classes `CentralMidi_DB`). Sempre guardar chamadas com `class_exists('CentralMidi_DB')` (padrão do `card-midi.php`).
- Transients: `centralmidi_home_*` (home, 6h), `centralmidi_slides` (carousel, 1 dia). Invalidados em `save_post_product` / `save_post_cm_slide` / `delete_post` via `CentralMidi_DB::clear_home_cache()`.
- Subir a **versão do tema** (`style.css`) ao alterar CSS/JS para quebrar cache (`?ver=`).
- Nunca commitar credenciais; manter usuário/senha só em `docker-compose.yml` (dev local).

## Verificação ao concluir tarefas

1. `php -l` em todos os arquivos PHP alterados (via container).
2. Node check de sintaxe dos JS alterados.
3. CSS balanceado: contar `{` e `}` em `main.css`.
4. `curl` nas rotas afetadas (200) e sem `Warning|Notice|Fatal` no HTML.