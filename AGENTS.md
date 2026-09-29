# AGENTS.md — Central MIDI

Projeto: loja WordPress + WooCommerce de arquivos MIDI, com plugin próprio
(`centralmidi/`) e tema `flatsome-child/` (dark mode por padrão, com tema claro
seguindo o dispositivo). Este diretório é **ao mesmo tempo** o repo git e o
workspace de staging local (espelho de produção).

## Layout do workspace (raiz = repo)

| Caminho | O que é |
|---|---|
| `atual/wp-content/plugins/centralmidi/` | Plugin Central MIDI (v1.2.4) — catálogo A-Z, admin, migração, player de demo. **Fonte única rastreada** (não duplicar) |
| `atual/wp-content/themes/flatsome-child/` | Tema child (depende do parent `flatsome/`, que **não** é versionado aqui). Fonte única rastreada |
| `HISTORICO-E-ARMADILHAS.md` | Memória do projeto (o que foi feito e por quê, armadilhas) |
| `PENDENCIAS.md` | Itens deliberadamente não corrigidos + roteiro de desacoplamento do tema |
| `conflitos-migracao-producao.md`, `produtos-para-revisar-pos-migracao.md` | Relatórios da migração 2026-09-27 |
| `atual/` | **Espelho de produção + WP instalado** (docker). Ignorado no git **exceto** o plugin e o tema acima — todo o resto (WP core, SQL, creds docker, `demos/` com 71 GB RO) fica fora por regra seletiva do `.gitignore` |
| `backup/`, `baseline/`, `relatorios/` | Dumps SQL e medições. Ignorados no git |
| `atual/.local-excluidos/` | Coisas removidas do projeto (docs antigos, backups de arquivos) — não apagar |

**Regra de ouro:** o repo contém **só código e documentação** — nunca credenciais,
dumps SQL nem dados de acesso à produção (varrido antes de subir).

## Ambiente local (docker em `atual/docker-compose.yml`)

- Site: <http://localhost:8090>
- phpMyAdmin: <http://127.0.0.1:8091>
- Banco: `local_midi` (container `local_db`), prefixo `wp_`, usuário `local_user`.
- Containers: `local_wp`, `local_db`, `local_pma`.

```bash
cd /mnt/dados_4tb/centralmidi/atual
docker compose up -d
docker compose down
```

`.gitignore` da raiz ignora do `atual/` tudo exceto `wp-content/plugins/centralmidi/`
e `wp-content/themes/flatsome-child/` (creds locais ficam no `docker-compose.yml`).

## Comandos úteis (workflow)

```bash
# Lint PHP (php-cli NÃO existe no host; usar o container)
docker exec local_wp php -l /var/www/html/wp-content/plugins/centralmidi/centralmidi.php

# MySQL direto (senha fica no docker-compose.yml de atual/)
docker exec local_db printenv MYSQL_PASSWORD
docker exec -i local_db mariadb -u local_user -p"$LDBP" local_midi -e "SELECT ..."
```

**Armadilha de dump/edição:** usar sempre `mysql/mariadb` com
`--raw --batch -N` para conteúdo de post — o dump "com literal \n" corrompe o
tamanho. Para SQL via stdin use `docker exec -i` (o `<` da shell não entrega o
SQL no `docker exec`). Conferir integridade com `MD5(post_content)='...'` no
mesmo UPDATE.

**Sincronizar com produção:** sempre pelo `./sincronizar-producao.sh`
(nunca `rsync` manual — `atual/` é espelho de produção e o `wp-content` do docker
é o mesmo diretório; as exclusões estão no próprio script e em `.rsync-filter`).

## Arquitetura (resumo)

- **Plugin `atual/wp-content/plugins/centralmidi/`:** classes em `includes/` — `class-centralmidi-artistas.php`
  (catálogo A-Z, roteia `/artistas/` e `/midis/`), `class-centralmidi-db.php`
  (queries SQL nativas em tabelas próprias `wp_centralmidi_*`), admin (metabox,
  tela MIDIs, importador em lote) e migração. Sem postmeta `_centralmidi_*`.
- **Tema `atual/wp-content/themes/flatsome-child/`:** player/howlet em `assets/`, CSS dark moderno
  (`style.css`, `assets/css/player.css`, `assets/css/custom-style.css`), header e
  footer 100% do child, seções da home estáticas com tokens `--cm-home-*`
  (dark e light), sobrescritas WooCommerce em `woocommerce/`.
- **Dependência do tema pai:** ainda usa `header.php` (shell), single-product,
  Minha Conta e o hero `[ux_slider]` do Flatsome — ver PENDENCIAS §2 (roteiro de
  desacoplamento).

## Convenções de documentação

- Item corrigido → **apagar o bloco** do `PENDENCIAS.md` e registrar o que/número
  em `HISTORICO-E-ARMADILHAS.md` (§1.x para migração, §2.x para desacoplamento).
- Item que virou irrelevante → apagar do PENDENCIAS **e dizer por quê** no HISTORICO.
- Backup de banco antes de editar linha de produção: dump em `backup/`.
- Não commitar/pushar sem pedido explícito do usuário. Ao mexer em `product_cat`,
  medir antes/depois (números de linha de base no cabeçalho do PENDENCIAS).