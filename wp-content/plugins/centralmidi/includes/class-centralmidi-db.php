<?php
/**
 * Central MIDI DB: custom tables for MIDI metadata.
 *
 * Tables:
 *   - wp_centralmidi_midis      (product_id, artista_id, genero_id, mes_lancamento, classificacao)
 *   - wp_centralmidi_artistas   (id, nome)
 *   - wp_centralmidi_generos    (id, nome)
 *
 * Classification: M (melody), L (lyrics), RLM (melody + lyrics)
 */

defined('ABSPATH') || exit;

class CentralMidi_DB {

    /**
     * Cache em memória de get_midis_by_products(), por product_id.
     *
     * @var array
     */
    private static $midi_cache = array();

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . CENTRALMIDI_TABLE;
    }

    public static function artistas_table_name() {
        global $wpdb;
        return $wpdb->prefix . CENTRALMIDI_ARTISTAS_TABLE;
    }

    public static function generos_table_name() {
        global $wpdb;
        return $wpdb->prefix . CENTRALMIDI_GENEROS_TABLE;
    }

    public static function create_table() {
        global $wpdb;

        $table_name = self::table_name();
        $charset    = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            artista_id BIGINT UNSIGNED NULL,
            genero_id BIGINT UNSIGNED NULL,
            mes_lancamento TINYINT UNSIGNED NOT NULL DEFAULT 0,
            ano_lancamento SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            classificacao VARCHAR(3) NOT NULL DEFAULT 'M',
            publicado TINYINT(1) NOT NULL DEFAULT 1,
            demo_audio VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY product_id (product_id),
            KEY artista_id (artista_id),
            KEY genero_id (genero_id),
            KEY mes_lancamento (mes_lancamento),
            KEY ano_lancamento (ano_lancamento),
            KEY classificacao (classificacao),
            KEY publicado (publicado)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('centralmidi_db_version', CENTRALMIDI_VERSION);
    }

    public static function create_artistas_table() {
        self::create_referencia_table(self::artistas_table_name(), true);
    }

    public static function create_generos_table() {
        self::create_referencia_table(self::generos_table_name());
    }

    /**
     * Create a simple reference table (id, nome, timestamps).
     */
    private static function create_referencia_table($table_name, $with_foto = false) {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $foto_column = $with_foto ? "foto_id BIGINT UNSIGNED NULL," : "";

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome VARCHAR(255) NOT NULL,
            {$foto_column}
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY nome (nome)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Run once per plugin version to apply schema upgrades and data migration.
     *
     * Guarded by `centralmidi_db_version`: without it every request would pay for
     * four INFORMATION_SCHEMA lookups plus a full-table UPDATE, which is not
     * viable once the catalog holds tens of thousands of products.
     */
    public static function maybe_upgrade() {
        global $wpdb;

        if (get_option('centralmidi_db_version') === CENTRALMIDI_VERSION) {
            return;
        }

        $midis_table = self::table_name();

        foreach (array(
            'artista'   => self::artistas_table_name(),
            'genero'    => self::generos_table_name(),
        ) as $kind => $table) {
            if (!self::table_exists($table)) {
                if ('artista' === $kind) {
                    self::create_artistas_table();
                } else {
                    self::create_generos_table();
                }
            }
        }

        // Ensure the artistas table has the foto_id column.
        $artistas_table = self::artistas_table_name();
        if (self::table_exists($artistas_table) && !self::column_exists($artistas_table, 'foto_id')) {
            $wpdb->query("ALTER TABLE {$artistas_table} ADD COLUMN foto_id BIGINT UNSIGNED NULL AFTER nome");
        }

        // Ensure the midis table has the FK columns.
        $after = 'product_id';
        foreach (array('artista_id', 'genero_id') as $col) {
            if (!self::column_exists($midis_table, $col)) {
                $wpdb->query("ALTER TABLE {$midis_table} ADD COLUMN {$col} BIGINT UNSIGNED NULL AFTER {$after}");
            }
            $after = $col;
        }

        // Ensure the midis table has the ano_lancamento column.
        if (!self::column_exists($midis_table, 'ano_lancamento')) {
            $wpdb->query("ALTER TABLE {$midis_table} ADD COLUMN ano_lancamento SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER mes_lancamento");
        }

        // Ensure the midis table has the publicado column (1 = available for sale).
        if (!self::column_exists($midis_table, 'publicado')) {
            $wpdb->query("ALTER TABLE {$midis_table} ADD COLUMN publicado TINYINT(1) NOT NULL DEFAULT 1 AFTER classificacao");
        }

        // Ensure the midis table has the demo_audio column, and backfill it from
        // the legacy postmeta key. The column only exists after this runs, so the
        // backfill stays inside the guard. Depois disso, a demo sai do postmeta.
        if (!self::column_exists($midis_table, 'demo_audio')) {
            $wpdb->query("ALTER TABLE {$midis_table} ADD COLUMN demo_audio VARCHAR(255) NOT NULL DEFAULT '' AFTER publicado");
            $wpdb->query(
                "UPDATE {$midis_table} m
                 JOIN {$wpdb->postmeta} pm
                   ON pm.post_id = m.product_id AND pm.meta_key = '_centralmidi_demo_audio'
                 SET m.demo_audio = pm.meta_value"
            );
        }

        // Backfill the year for legacy rows created before the column existed.
        $wpdb->query("UPDATE {$midis_table} SET ano_lancamento = YEAR(created_at) WHERE ano_lancamento = 0 AND created_at IS NOT NULL");

        // Migrate legacy denormalized strings to the reference tables.
        self::migrate_legacy_data();

        // Drop the legacy denormalized string columns once migration is done.
        foreach (array('artista', 'genero') as $col) {
            if (self::column_exists($midis_table, $col)) {
                $wpdb->query("ALTER TABLE {$midis_table} DROP COLUMN {$col}");
            }
        }

        // Stamp the version last, so an interrupted upgrade runs again. Leaving the
        // stamp off when the collation fix failed keeps the guard at the top open,
        // so the next request retries instead of inheriting a broken schema for
        // good. Every step above is guarded by column_exists(), so re-running is safe.
        if (self::align_collations()) {
            update_option('centralmidi_db_version', CENTRALMIDI_VERSION);
        }
    }

    /**
     * Align our table collations with the ones WordPress already uses.
     *
     * The plugin tables are created with $wpdb->get_charset_collate(), which on
     * WordPress 6.x is utf8mb4_unicode_520_ci. Sites imported from older
     * WordPress installs keep utf8mb4_unicode_ci on wp_posts/wp_terms, and any
     * query comparing an artist name against a legacy term then dies with:
     *   ERROR 1267 (HY000): Illegal mix of collations
     *
     * Rather than hardcoding a collation, follow whatever the site itself uses,
     * so the catalog tables always join cleanly against the core tables.
     *
     * @return bool True only when every existing table is on the reference collation.
     */
    private static function align_collations() {
        global $wpdb;

        $reference = $wpdb->get_row($wpdb->prepare(
            "SELECT c.CHARACTER_SET_NAME, t.TABLE_COLLATION
               FROM INFORMATION_SCHEMA.TABLES AS t
               JOIN INFORMATION_SCHEMA.COLUMNS AS c
                 ON c.TABLE_SCHEMA = t.TABLE_SCHEMA
                AND c.TABLE_NAME  = t.TABLE_NAME
                AND c.COLLATION_NAME = t.TABLE_COLLATION
              WHERE t.TABLE_SCHEMA = %s AND t.TABLE_NAME = %s
              LIMIT 1",
            DB_NAME,
            $wpdb->posts
        ));
        if (!$reference || empty($reference->CHARACTER_SET_NAME) || empty($reference->TABLE_COLLATION)) {
            // Loud on purpose: silently skipping here leaves a schema that only
            // breaks much later, inside a query nobody suspects.
            error_log('CentralMidi: align_collations() could not read the reference collation from ' . $wpdb->posts);
            return false;
        }

        $charset   = $reference->CHARACTER_SET_NAME;
        $collation = $reference->TABLE_COLLATION;
        $aligned   = true;

        foreach (array(
            self::table_name(),
            self::artistas_table_name(),
            self::generos_table_name(),
        ) as $table) {
            if (!self::table_exists($table)) {
                continue;
            }
            $current = $wpdb->get_var($wpdb->prepare(
                "SELECT TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES
                  WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
                DB_NAME,
                $table
            ));
            if ($current === $collation) {
                continue;
            }
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- identifiers come from self::table_name().
            $converted = $wpdb->query("ALTER TABLE {$table} CONVERT TO CHARACTER SET {$charset} COLLATE {$collation}");
            if (false === $converted) {
                $aligned = false;
                error_log(sprintf(
                    'CentralMidi: failed to align %s from %s to %s: %s',
                    $table,
                    (string) $current,
                    $collation,
                    $wpdb->last_error
                ));
            }
        }

        return $aligned;
    }

    /**
     * Forget the schema version and re-run the upgrade on the next load.
     */
    public static function force_upgrade() {
        delete_option('centralmidi_db_version');
    }

    public static function table_exists($table) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $table
        ));
    }

    private static function column_exists($table, $column) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $table,
            $column
        ));
    }

    /**
     * One-time migration: create reference rows from the legacy denormalized
     * `artista` / `genero` string columns and link them via IDs.
     */
    private static function migrate_legacy_data() {
        global $wpdb;

        $midis_table = self::table_name();

        if (!self::column_exists($midis_table, 'artista') && !self::column_exists($midis_table, 'genero')) {
            return;
        }

        // Link legacy artist strings that have no artist_id yet.
        if (self::column_exists($midis_table, 'artista')) {
            $rows = $wpdb->get_results(
                "SELECT id, artista FROM {$midis_table} WHERE artista <> '' AND artista_id IS NULL"
            );
            foreach ($rows as $row) {
                $aid = self::add_artista($row->artista);
                if ($aid) {
                    $wpdb->update($midis_table, array('artista_id' => $aid), array('id' => $row->id));
                }
            }
        }

        // Link legacy genre strings.
        if (self::column_exists($midis_table, 'genero')) {
            $rows = $wpdb->get_results(
                "SELECT id, genero FROM {$midis_table} WHERE genero <> '' AND genero_id IS NULL"
            );
            foreach ($rows as $row) {
                $gid = self::add_genero($row->genero);
                if ($gid) {
                    $wpdb->update($midis_table, array('genero_id' => $gid), array('id' => $row->id));
                }
            }
        }
    }

    public static function drop_table() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS " . self::table_name());
        $wpdb->query("DROP TABLE IF EXISTS " . self::artistas_table_name());
        $wpdb->query("DROP TABLE IF EXISTS " . self::generos_table_name());
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}centralmidi_categorias");
    }

    /**
     * Upsert one row for a product.
     *
     * @param int $product_id
     * @param array $data Keys: artista_id, genero_id, mes_lancamento, classificacao
     */
    public static function upsert($product_id, $data) {
        global $wpdb;

        $table_name = self::table_name();
        $now        = current_time('mysql');

        $artista_id = isset($data['artista_id']) ? absint($data['artista_id']) : 0;
        $genero_id  = isset($data['genero_id']) ? absint($data['genero_id']) : 0;

        // Resolve reference IDs from legacy names when only the name was given.
        if (!$artista_id && !empty($data['artista'])) {
            $artista_id = self::add_artista($data['artista']);
        }
        if (!$genero_id && !empty($data['genero'])) {
            $genero_id = self::add_genero($data['genero']);
        }

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT id, publicado FROM {$table_name} WHERE product_id = %d", $product_id)
        );

        $payload = array(
            'product_id'     => (int) $product_id,
            'artista_id'     => $artista_id ? $artista_id : null,
            'genero_id'      => $genero_id ? $genero_id : null,
            'mes_lancamento' => absint($data['mes_lancamento'] ?? 0),
            'ano_lancamento' => absint($data['ano_lancamento'] ?? 0),
            'classificacao'  => self::sanitize_classificacao($data['classificacao'] ?? 'M'),
            'updated_at'     => $now,
        );

        // demo_audio é opcional: só entra no payload quando informado, para não
        // zerar a demo de quem chama upsert() só para atualizar outros campos.
        if (array_key_exists('demo_audio', $data)) {
            $payload['demo_audio'] = (string) $data['demo_audio'];
        }

        if ($row) {
            // Preserve the current publicado flag unless explicitly provided.
            $payload['publicado'] = isset($data['publicado']) ? (int) (bool) $data['publicado'] : (int) $row->publicado;
            $wpdb->update($table_name, $payload, array('id' => $row->id));
        } else {
            $payload['publicado']  = isset($data['publicado']) ? (int) (bool) $data['publicado'] : 1;
            $payload['created_at'] = $now;
            $wpdb->insert($table_name, $payload);
        }

        self::flush_midi_cache();
    }

    /**
     * Atualiza SÓ a coluna demo_audio, sem mexer nos demais campos.
     *
     * upsert() reescreve o registro inteiro e zera mes/ano quando recebe payload
     * parcial — por isso este caminho dirigido existe para edições isoladas da
     * demo (ex.: edição inline na /ferramentas-admin/).
     */
    public static function set_demo_audio($product_id, $value) {
        global $wpdb;

        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return;
        }

        $table = self::table_name();
        $now   = current_time('mysql');
        $value = (string) $value;

        $existe = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d", $product_id)
        );

        if ($existe) {
            $wpdb->update(
                $table,
                array('demo_audio' => $value, 'updated_at' => $now),
                array('product_id' => $product_id)
            );
        } else {
            $wpdb->insert($table, array(
                'product_id' => $product_id,
                'demo_audio' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }

        self::flush_midi_cache();
    }

    /**
     * Delete a row for a product.
     */
    public static function delete($product_id) {
        global $wpdb;
        $wpdb->delete(self::table_name(), array('product_id' => (int) $product_id));
        self::flush_midi_cache();
    }

    /* ------------------------------------------------------------------
     * Artistas
     * ---------------------------------------------------------------- */

    public static function get_artistas() {
        return self::get_referencias(self::artistas_table_name());
    }

    public static function get_artista($id) {
        return self::get_referencia(self::artistas_table_name(), $id);
    }

    public static function get_artista_by_nome($nome) {
        return self::get_referencia_by_nome(self::artistas_table_name(), $nome);
    }

    /**
     * Insert a new artist, returning its ID (or existing ID if name already present).
     */
    public static function add_artista($nome, $foto_id = 0) {
        return self::add_referencia(self::artistas_table_name(), $nome, array('foto_id' => $foto_id));
    }

    public static function update_artista($id, $nome, $foto_id = null) {
        $extra = (null !== $foto_id) ? array('foto_id' => $foto_id) : array();
        return self::update_referencia(self::artistas_table_name(), $id, $nome, $extra);
    }

    public static function delete_artista($id) {
        return self::delete_referencia(self::artistas_table_name(), 'artista_id', $id);
    }

    public static function artistas_count() {
        return self::referencias_count(self::artistas_table_name());
    }

    /**
     * HTML of the artist photo (attachment), or '' when there is none.
     *
     * @param int $artista_id
     * @param string|array $size
     * @return string
     */
    public static function get_artista_foto_html($artista_id, $size = 'medium') {
        $a = self::get_artista($artista_id);
        if ($a && $a->foto_id) {
            return wp_get_attachment_image((int) $a->foto_id, $size);
        }
        return '';
    }

    /**
     * Get artists with MIDI counts, filtered by letter (A-Z, 0-9/outros) and search text.
     */
    public static function get_artistas_alfabetico($letra = '', $busca = '') {
        global $wpdb;
        $artistas_table = self::artistas_table_name();
        $midis_table = self::table_name();

        $where = array("1=1");
        $params = array();

        $letra = strtoupper(trim((string) $letra));
        if ($letra === '0-9' || $letra === 'OUTROS' || $letra === '#') {
            $where[] = "a.nome REGEXP '^[^A-Za-z]'";
        } elseif ($letra !== '' && $letra !== 'TODOS' && preg_match('/^[A-Z]$/', $letra)) {
            $where[] = "a.nome LIKE %s";
            $params[] = $letra . '%';
        }

        if (!empty($busca)) {
            $where[] = "a.nome LIKE %s";
            $params[] = '%' . $wpdb->esc_like(trim($busca)) . '%';
        }

        $sql = "SELECT a.id, a.nome, a.foto_id, COUNT(m.id) as total_midis 
                FROM {$artistas_table} a 
                LEFT JOIN {$midis_table} m ON (m.artista_id = a.id AND m.publicado = 1)
                WHERE " . implode(' AND ', $where) . "
                GROUP BY a.id, a.nome, a.foto_id
                ORDER BY a.nome ASC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Iniciais que têm ao menos um artista com MIDI, em ordem alfabética.
     *
     * Substitui a consulta que fazia DISTINCT sobre wp_terms: o menu A-Z precisa
     * só das iniciais, não dos 13 mil artistas. Traz '#' quando existe artista
     * começando por caractere não-alfabético.
     *
     * @return string[]
     */
    public static function get_iniciais_com_artistas() {
        global $wpdb;
        $artistas_table = self::artistas_table_name();
        $midis_table    = self::table_name();

        $rows = $wpdb->get_results(
            "SELECT DISTINCT UPPER(LEFT(a.nome, 1)) AS inicial
               FROM {$artistas_table} AS a
               JOIN {$midis_table} AS m ON m.artista_id = a.id
              ORDER BY inicial ASC"
        );

        $out = array();
        foreach ((array) $rows as $row) {
            $out[] = (string) $row->inicial;
        }
        return $out;
    }

    /* ------------------------------------------------------------------
     * Gêneros
     * ---------------------------------------------------------------- */

    public static function get_generos() {
        return self::get_referencias(self::generos_table_name());
    }

    public static function get_genero($id) {
        return self::get_referencia(self::generos_table_name(), $id);
    }

    public static function get_genero_by_nome($nome) {
        return self::get_referencia_by_nome(self::generos_table_name(), $nome);
    }

    public static function add_genero($nome) {
        return self::add_referencia(self::generos_table_name(), $nome);
    }

    public static function update_genero($id, $nome) {
        return self::update_referencia(self::generos_table_name(), $id, $nome);
    }

    public static function delete_genero($id) {
        return self::delete_referencia(self::generos_table_name(), 'genero_id', $id);
    }

    public static function generos_count() {
        return self::referencias_count(self::generos_table_name());
    }

    /* ------------------------------------------------------------------
     * Helpers genéricos de referência
     * ---------------------------------------------------------------- */

    private static function get_referencias($table) {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY nome ASC");
    }

    private static function get_referencia($table, $id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", absint($id)));
    }

    private static function get_referencia_by_nome($table, $nome) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE nome = %s", trim($nome)));
    }

    /**
     * Insert a new reference row, returning its ID (or existing ID if name already present).
     */
    private static function add_referencia($table, $nome, $extra = array()) {
        global $wpdb;

        $nome = sanitize_text_field($nome);
        if ('' === $nome) {
            return 0;
        }

        $existing = self::get_referencia_by_nome($table, $nome);
        if ($existing) {
            if (isset($extra['foto_id']) && self::column_exists($table, 'foto_id')) {
                $wpdb->update($table, array('foto_id' => absint($extra['foto_id']) ? absint($extra['foto_id']) : null), array('id' => $existing->id));
            }
            return (int) $existing->id;
        }

        $now     = current_time('mysql');
        $payload = array(
            'nome'       => $nome,
            'created_at' => $now,
            'updated_at' => $now,
        );

        if (isset($extra['foto_id']) && self::column_exists($table, 'foto_id')) {
            $payload['foto_id'] = absint($extra['foto_id']) ? absint($extra['foto_id']) : null;
        }

        $wpdb->insert($table, $payload);

        return (int) $wpdb->insert_id;
    }

    private static function update_referencia($table, $id, $nome, $extra = array()) {
        global $wpdb;

        $nome = sanitize_text_field($nome);
        if ('' === $nome) {
            return false;
        }

        // Avoid duplicate names.
        $dup = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE nome = %s AND id <> %d", $nome, absint($id))
        );
        if ($dup) {
            return false;
        }

        $data = array(
            'nome'       => $nome,
            'updated_at' => current_time('mysql'),
        );

        if (isset($extra['foto_id']) && self::column_exists($table, 'foto_id')) {
            $data['foto_id'] = absint($extra['foto_id']) ? absint($extra['foto_id']) : null;
        }

        $updated = $wpdb->update($table, $data, array('id' => absint($id)));

        return false !== $updated;
    }

    /**
     * Delete a reference row after resetting its FK references in the midis table.
     */
    private static function delete_referencia($table, $midis_column, $id) {
        global $wpdb;

        // Reset references before removing the row.
        $wpdb->update(
            self::table_name(),
            array($midis_column => null),
            array($midis_column => absint($id))
        );

        return $wpdb->delete($table, array('id' => absint($id)));
    }

    private static function referencias_count($table) {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    public static function sanitize_classificacao($value) {
        $value = strtoupper(trim((string) $value));
        if ('' === $value || 'NONE' === $value) {
            return '';
        }
        return in_array($value, array('M', 'L', 'RLM'), true) ? $value : '';
    }

    public static function classificacao_label($value) {
        $labels = array(
            'M'   => __('MIDI somente com Melodia', 'centralmidi'),
            'L'   => __('MIDI somente com Letra sincronizada', 'centralmidi'),
            'RLM' => __('MIDI com Melodia e Letra sincronizada', 'centralmidi'),
        );
        return $labels[self::sanitize_classificacao($value)] ?? '';
    }

    public static function mes_nome($mes) {
        $meses = array(
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        );
        return $meses[absint($mes)] ?? '';
    }

    /**
     * Distinct values used to populate filter dropdowns.
     *
     * @param string $column artista|genero|mes_lancamento|classificacao
     * @return array
     */
    public static function distinct($column) {
        global $wpdb;

        $midis_table = self::table_name();

        if ('artista' === $column) {
            $table = self::artistas_table_name();
            $sql   = "SELECT DISTINCT a.nome AS val FROM {$midis_table} m INNER JOIN {$table} a ON a.id = m.artista_id WHERE a.nome <> '' AND m.publicado = 1 ORDER BY a.nome ASC";
            return $wpdb->get_col($sql);
        }

        if ('genero' === $column) {
            $table = self::generos_table_name();
            $sql   = "SELECT DISTINCT g.nome AS val FROM {$midis_table} m INNER JOIN {$table} g ON g.id = m.genero_id WHERE g.nome <> '' AND m.publicado = 1 ORDER BY g.nome ASC";
            return $wpdb->get_col($sql);
        }

        $allowed = array('mes_lancamento', 'classificacao');
        if (!in_array($column, $allowed, true)) {
            return array();
        }

        $query   = "SELECT DISTINCT {$column} AS val FROM {$midis_table} WHERE {$column} <> '' AND publicado = 1 ORDER BY val ASC";
        $results = $wpdb->get_col($query);

        return array_filter($results);
    }

    /**
     * Get random product IDs for a specific launch month (up to $limit, e.g. 30).
     */
    public static function get_random_midis_by_month($month, $limit = 30) {
        global $wpdb;
        $table_name = self::table_name();
        $sql = $wpdb->prepare(
            "SELECT product_id FROM {$table_name} WHERE mes_lancamento = %d AND publicado = 1 ORDER BY RAND() LIMIT %d",
            absint($month),
            absint($limit)
        );
        return array_map('intval', $wpdb->get_col($sql));
    }

    /**
     * Months (with releases) for a given year, newest first.
     *
     * @param int $ano
     * @return int[]
     */
    public static function get_meses_por_ano($ano) {
        global $wpdb;
        $table_name = self::table_name();
        $sql = $wpdb->prepare(
            "SELECT DISTINCT mes_lancamento FROM {$table_name} WHERE ano_lancamento = %d AND mes_lancamento <> 0 AND publicado = 1 ORDER BY mes_lancamento DESC",
            absint($ano)
        );
        return array_map('intval', $wpdb->get_col($sql));
    }

    /**
     * Todos os gêneros com a contagem de MIDIs, em ordem alfabética.
     *
     * Mantém gêneros sem nenhum MIDI, com qtd = 0, porque a listagem pública
     * usava hide_empty => false e exibia os 25. Uma query só, com LEFT JOIN.
     *
     * @return array[] Cada item: nome, qtd.
     */
    public static function get_generos_com_contagem() {
        global $wpdb;
        $generos_table = self::generos_table_name();
        $table_name    = self::table_name();
        $rows = $wpdb->get_results(
            "SELECT g.nome, COUNT(m.id) AS qtd
               FROM {$generos_table} AS g
          LEFT JOIN {$table_name} AS m ON m.genero_id = g.id
           GROUP BY g.id, g.nome
           ORDER BY g.nome ASC"
        );

        $out = array();
        foreach ((array) $rows as $row) {
            $out[] = array(
                'nome' => (string) $row->nome,
                'qtd'  => (int) $row->qtd,
            );
        }
        return $out;
    }

    /**
     * Todos os meses com lançamentos, do mais novo para o mais antigo.
     *
     * Substitui a consulta sobre a taxonomia legada `mes_de_lancamento`, que
     * trazia slug + nome + contagem. Não há mais slug: o chamador precisa usar
     * CentralMidi_Frontend::mes_label() para exibir o texto.
     *
     * @return array[] Cada item: mes, ano, qtd.
     */
    public static function get_meses_disponiveis() {
        global $wpdb;
        $table_name = self::table_name();
        $rows = $wpdb->get_results(
            "SELECT mes_lancamento, ano_lancamento, COUNT(*) AS qtd
               FROM {$table_name}
              WHERE mes_lancamento > 0 AND ano_lancamento > 0
              GROUP BY mes_lancamento, ano_lancamento
              ORDER BY ano_lancamento DESC, mes_lancamento DESC"
        );

        $out = array();
        foreach ((array) $rows as $row) {
            $out[] = array(
                'mes' => absint($row->mes_lancamento),
                'ano' => absint($row->ano_lancamento),
                'qtd' => (int) $row->qtd,
            );
        }
        return $out;
    }

    /**
     * Get product IDs for a specific launch month/year (up to $limit), newest first.
     * Deterministic ordering (no RAND()) so pages are stable and cacheable.
     */
    public static function get_midis_by_month($month, $ano = 0, $limit = 30) {
        global $wpdb;
        $table_name = self::table_name();
        if (!$ano) {
            $ano = (int) date('Y');
        }
        $sql = $wpdb->prepare(
            "SELECT product_id FROM {$table_name} WHERE mes_lancamento = %d AND ano_lancamento = %d AND publicado = 1 ORDER BY created_at DESC, product_id DESC LIMIT %d",
            absint($month),
            absint($ano),
            absint($limit)
        );
        return array_map('intval', $wpdb->get_col($sql));
    }

    /**
     * Total count of MIDIs for a given month/year.
     */
    public static function count_by_month($month, $ano = 0) {
        global $wpdb;
        $table_name = self::table_name();
        if (!$ano) {
            $ano = (int) date('Y');
        }
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE mes_lancamento = %d AND ano_lancamento = %d AND publicado = 1",
            absint($month),
            absint($ano)
        );
        return (int) $wpdb->get_var($sql);
    }

    /**
     * Returns product IDs matching all given filters (AND).
     *
     * @param array $filters Keys: artista, genero, mes_lancamento, classificacao
     * @return array|int[] product IDs
     */
    public static function search_product_ids($filters = array()) {
        global $wpdb;

        $midis_table    = self::table_name();
        $artistas_table = self::artistas_table_name();
        $generos_table  = self::generos_table_name();

        $joins  = array();
        $where  = array('m.publicado = 1');
        $params = array();

        if (!empty($filters['artista'])) {
            $joins[]  = "INNER JOIN {$artistas_table} a ON a.id = m.artista_id";
            $where[]  = "a.nome = %s";
            $params[] = $filters['artista'];
        }

        if (!empty($filters['genero'])) {
            $joins[]  = "INNER JOIN {$generos_table} g ON g.id = m.genero_id";
            $where[]  = "g.nome = %s";
            $params[] = $filters['genero'];
        }

        if (!empty($filters['mes_lancamento'])) {
            $where[]  = "m.mes_lancamento = %d";
            $params[] = absint($filters['mes_lancamento']);
        }

        if (!empty($filters['classificacao'])) {
            $where[]  = "m.classificacao = %s";
            $params[] = $filters['classificacao'];
        }

        $sql = "SELECT m.product_id FROM {$midis_table} m " . implode(' ', array_unique($joins)) . " WHERE " . implode(' AND ', $where);

        if ($params) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return array_map('intval', $wpdb->get_col($sql));
    }

    /**
     * Clear the cached homepage monthly-release data.
     */
    public static function clear_home_cache() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_centralmidi_home_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_centralmidi_home_%'");
    }

    /**
     * ID of the canonical catalog page (the page that renders [centralmidi_catalogo]),
     * cached in a single wp_options row so we never scan all pages on every request.
     *
     * @return int
     */
    public static function catalog_page_id() {
        $id = (int) get_option('centralmidi_catalog_page_id');

        if ($id > 0 && 'publish' === get_post_status($id)) {
            return $id;
        }

        $id = 0;
        $pages = get_pages(array(
            'post_type'   => 'page',
            'post_status' => 'publish',
            'sort_column' => 'menu_order,ID',
        ));

        foreach ($pages as $page) {
            if (has_shortcode($page->post_content, 'centralmidi_catalogo')) {
                $id = (int) $page->ID;
                break;
            }
        }

        update_option('centralmidi_catalog_page_id', $id);

        return $id;
    }

    /**
     * Canonical catalog URL, cached in a single wp_options row.
     *
     * @return string
     */
    public static function catalog_url() {
        $id = self::catalog_page_id();
        return $id ? get_permalink($id) : home_url('/midis/');
    }

    /**
     * Invalidate the cached catalog page ID (next call re-resolves it).
     */
    public static function refresh_catalog_url_cache() {
        delete_option('centralmidi_catalog_page_id');
    }

    /**
     * Search MIDIs by artist or genre name (LIKE).
     *
     * @param string $term Search term.
     * @return int[] product IDs
     */
    public static function search_by_term($term) {
        global $wpdb;

        $midis_table    = self::table_name();
        $artistas_table = self::artistas_table_name();
        $generos_table  = self::generos_table_name();

        $like = '%' . $wpdb->esc_like(trim($term)) . '%';
        $sql = $wpdb->prepare(
            "SELECT DISTINCT m.product_id
             FROM {$midis_table} m
             LEFT JOIN {$artistas_table} a ON a.id = m.artista_id
             LEFT JOIN {$generos_table} g ON g.id = m.genero_id
             WHERE m.publicado = 1 AND (a.nome LIKE %s OR g.nome LIKE %s)",
            $like,
            $like
        );
        return array_map('intval', $wpdb->get_col($sql));
    }

    /**
     * Admin listing of MIDIs (with product/artist/genre info), paginated.
     *
     * @param array $filters Keys: busca, artista_id, genero_id, mes, ano, classificacao
     * @param int $per_page
     * @param int $page
     * @param int|null $total Filled with the total row count (pass by reference).
     * @param string $sort_by Whitelisted column: titulo|artista|genero|mes|ano|classificacao|id
     * @param string $sort_dir ASC|DESC
     * @return object[]
     */
    public static function get_midis_admin($filters = array(), $per_page = 20, $page = 1, &$total = null, $sort_by = '', $sort_dir = 'DESC') {
        global $wpdb;

        $midis_table    = self::table_name();
        $artistas_table = self::artistas_table_name();
        $generos_table  = self::generos_table_name();

        $where  = array('1=1');
        $params = array();

        if (!empty($filters['busca'])) {
            $where[]  = "p.post_title LIKE %s";
            $params[] = '%' . $wpdb->esc_like($filters['busca']) . '%';
        }
        if (!empty($filters['artista_id'])) {
            $where[]  = "m.artista_id = %d";
            $params[] = absint($filters['artista_id']);
        }
        if (!empty($filters['genero_id'])) {
            $where[]  = "m.genero_id = %d";
            $params[] = absint($filters['genero_id']);
        }
        if (!empty($filters['artista'])) {
            $where[]  = "a.nome = %s";
            $params[] = $filters['artista'];
        }
        if (!empty($filters['genero'])) {
            $where[]  = "g.nome = %s";
            $params[] = $filters['genero'];
        }
        if (!empty($filters['mes'])) {
            $where[]  = "m.mes_lancamento = %d";
            $params[] = absint($filters['mes']);
        }
        if (!empty($filters['ano'])) {
            $where[]  = "m.ano_lancamento = %d";
            $params[] = absint($filters['ano']);
        }
        if (!empty($filters['classificacao'])) {
            $where[]  = "m.classificacao = %s";
            $params[] = $filters['classificacao'];
        }
        if (isset($filters['publicado']) && in_array((int) $filters['publicado'], array(0, 1), true)) {
            $where[]  = "m.publicado = %d";
            $params[] = (int) $filters['publicado'];
        }

        $where_sql = implode(' AND ', $where);
        $join_sql  = "LEFT JOIN {$wpdb->posts} p ON p.ID = m.product_id";
        $join_sql .= " LEFT JOIN {$artistas_table} a ON a.id = m.artista_id";
        $join_sql .= " LEFT JOIN {$generos_table} g ON g.id = m.genero_id";

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$midis_table} m {$join_sql} WHERE {$where_sql}",
            $params
        ));

        // Whitelisted ORDER BY to avoid SQL injection.
        $sort_columns = array(
            'titulo'        => 'p.post_title',
            'artista'       => 'a.nome',
            'genero'        => 'g.nome',
            'mes'           => 'm.mes_lancamento',
            'ano'           => 'm.ano_lancamento',
            'classificacao' => 'm.classificacao',
            'publicado'     => 'm.publicado',
            'id'            => 'm.product_id',
        );
        $sort_dir = strtoupper($sort_dir) === 'ASC' ? 'ASC' : 'DESC';
        $order_sql = isset($sort_columns[$sort_by])
            ? "ORDER BY {$sort_columns[$sort_by]} {$sort_dir}"
            : "ORDER BY m.updated_at DESC, m.product_id DESC";

        $page     = max(1, absint($page));
        $per_page = max(1, absint($per_page));
        $offset   = ($page - 1) * $per_page;

        $sql = $wpdb->prepare(
            "SELECT m.id, m.product_id, m.artista_id, m.genero_id, m.mes_lancamento,
                    m.ano_lancamento, m.classificacao, m.publicado, a.nome AS artista, g.nome AS genero,
                    p.post_title AS titulo
             FROM {$midis_table} m {$join_sql}
             WHERE {$where_sql}
             {$order_sql}
             LIMIT %d OFFSET %d",
            array_merge($params, array($per_page, $offset))
        );

        return $wpdb->get_results($sql);
    }

    /* ------------------------------------------------------------------
     * Leitura consolidada por produto
     *
     * Usada pelo child theme para não ler wp_postmeta. Os escalares (mês, ano,
     * classificação, gênero) vêm das tabelas; a lista de artistas vem do
     * product_cat, que segue como taxonomia de artistas e suporta N por produto.
     * ------------------------------------------------------------------ */

    /**
     * Dados de um MIDI. Wrapper de get_midis_by_products() para um único ID.
     *
     * @param int $product_id
     * @return array|null
     */
    public static function get_midi_by_product($product_id) {
        $product_id = (int) $product_id;
        if ($product_id <= 0) {
            return null;
        }
        $map = self::get_midis_by_products(array($product_id));
        return isset($map[$product_id]) ? $map[$product_id] : null;
    }

    /**
     * Dados de vários MIDIs em quatro consultas (tabela, gêneros já vêm no JOIN,
     * demo, artistas), evitando N+1 na home e na busca.
     *
     * IDs sem linha na tabela ainda retornam um registro com os valores padrão,
     * para que a vitrine não quebre em produto legado.
     *
     * @param int[] $product_ids
     * @return array<int, array> mapeado por product_id
     */
    public static function get_midis_by_products($product_ids) {
        global $wpdb;

        $ids = array_values(array_unique(array_filter(array_map('absint', (array) $product_ids))));
        if (!$ids) {
            return array();
        }

        $out  = array();
        $todo = array();

        foreach ($ids as $id) {
            if (isset(self::$midi_cache[$id])) {
                $out[$id] = self::$midi_cache[$id];
            } else {
                $todo[] = $id;
            }
        }
        if (!$todo) {
            return $out;
        }

        $midis_table    = self::table_name();
        $artistas_table = self::artistas_table_name();
        $generos_table  = self::generos_table_name();

        $artistas_por_produto = array();

        foreach (array_chunk($todo, 500) as $chunk) {
            $ids_flat = implode(',', $chunk);

            $rows = $wpdb->get_results(
                "SELECT m.product_id, m.artista_id, m.genero_id, m.mes_lancamento,
                        m.ano_lancamento, m.classificacao, m.publicado, m.demo_audio,
                        a.nome AS artista_nome, a.foto_id AS artista_foto_id,
                        g.nome AS genero
                 FROM {$midis_table} m
                 LEFT JOIN {$artistas_table} a ON a.id = m.artista_id
                 LEFT JOIN {$generos_table} g ON g.id = m.genero_id
                 WHERE m.product_id IN ({$ids_flat})"
            );

            foreach ($rows as $row) {
                $pid = (int) $row->product_id;
                $out[$pid] = array(
                    'product_id'     => $pid,
                    'artista_id'     => (int) $row->artista_id,
                    'artista_nome'   => (string) $row->artista_nome,
                    'artista_foto_id' => (int) $row->artista_foto_id,
                    'genero_id'      => (int) $row->genero_id,
                    'genero'         => (string) $row->genero,
                    'mes_lancamento' => (int) $row->mes_lancamento,
                    'ano_lancamento' => (int) $row->ano_lancamento,
                    'classificacao'  => self::sanitize_classificacao($row->classificacao),
                    'publicado'      => (int) $row->publicado,
                    'demo_raw'       => (string) $row->demo_audio,
                );
            }

            $term_rows = $wpdb->get_results(
                "SELECT tr.object_id, t.term_id, t.name
                 FROM {$wpdb->term_relationships} tr
                 JOIN {$wpdb->term_taxonomy} tt
                   ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
                 JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                 WHERE tr.object_id IN ({$ids_flat})
                 ORDER BY t.name ASC"
            );
            foreach ($term_rows as $term) {
                $artistas_por_produto[(int) $term->object_id][] = array(
                    'id'   => (int) $term->term_id,
                    'nome' => (string) $term->name,
                );
            }
        }

        foreach ($todo as $pid) {
            if (!isset($out[$pid])) {
                $out[$pid] = array(
                    'product_id'     => $pid,
                    'artista_id'     => 0,
                    'artista_nome'   => '',
                    'artista_foto_id' => 0,
                    'genero_id'      => 0,
                    'genero'         => '',
                    'mes_lancamento' => 0,
                    'ano_lancamento' => 0,
                    'classificacao'  => '',
                    'publicado'      => 0,
                    'demo_raw'       => '',
                );
            }

            $artistas = isset($artistas_por_produto[$pid]) ? $artistas_por_produto[$pid] : array();
            $nomes    = array();
            $ids_art  = array();
            foreach ($artistas as $artista) {
                $ids_art[] = (int) $artista['id'];
                $nomes[]   = (string) $artista['nome'];
            }

            // midis.artista_id é o artista principal: ele vem primeiro na exibição,
            // os demais ficam em ordem alfabética.
            $primario = isset($out[$pid]['artista_nome']) ? $out[$pid]['artista_nome'] : '';
            if ($primario !== '' && count($nomes) > 1) {
                $idx = array_search($primario, $nomes, true);
                if ($idx !== false && $idx > 0) {
                    unset($nomes[$idx], $ids_art[$idx]);
                    array_unshift($nomes, $primario);
                    array_unshift($ids_art, (int) $artistas[$idx]['id']);
                    $nomes   = array_values($nomes);
                    $ids_art = array_values($ids_art);
                }
            }

            $demo_raw = isset($out[$pid]['demo_raw']) ? $out[$pid]['demo_raw'] : '';

            $out[$pid]['artista_ids']   = $ids_art;
            $out[$pid]['artistas']      = $nomes;
            $out[$pid]['artista']       = implode(' & ', $nomes);
            $out[$pid]['demo_raw']      = $demo_raw;
            $out[$pid]['demo_url']      = self::resolve_media_url(
                $demo_raw,
                $out[$pid]['mes_lancamento'],
                $out[$pid]['ano_lancamento']
            );

            self::$midi_cache[$pid] = $out[$pid];
            $out[$pid] = self::$midi_cache[$pid];
        }

        return $out;
    }

    /**
     * Anexa à consulta os filtros de demo, sem vazar detalhes para o child.
     *
     * A demo é a coluna `demo_audio` de wp_centralmidi_midis (mesmo nome de
     * coluna em toda a API). Produto sem linha na tabela conta como "sem demo".
     *
     * @param string $mode  'has', 'none' ou '' (sem filtro)
     * @param string $search texto parcial da URL da demo
     * @param string $join  referência ao trecho JOIN being montado
     * @param string $where referência ao trecho WHERE being montado
     */
    public static function apply_demo_filter($mode, $search, &$join, &$where) {
        global $wpdb;

        $table    = self::table_name();
        $pesquisa = trim((string) $search);

        if ('none' === $mode) {
            $join  .= " LEFT JOIN {$table} mdemo ON mdemo.product_id=p.ID AND mdemo.demo_audio!=''";
            $where .= " AND mdemo.product_id IS NULL";
            return;
        }

        if ('has' === $mode || '' !== $pesquisa) {
            $join .= " JOIN {$table} mdemo ON mdemo.product_id=p.ID";
        }

        if ('has' === $mode) {
            $where .= " AND mdemo.demo_audio != ''";
        }

        if ('' !== $pesquisa) {
            $where .= $wpdb->prepare(
                " AND mdemo.demo_audio LIKE %s",
                '%' . $wpdb->esc_like($pesquisa) . '%'
            );
        }
    }

    /**
     * Quantos produtos publicados têm demo cadastrada.
     */
    public static function count_products_with_demo() {
        global $wpdb;

        return (int) $wpdb->get_var(
            "SELECT COUNT(*)
             FROM " . self::table_name() . " m
             JOIN {$wpdb->posts} p ON p.ID = m.product_id
             WHERE p.post_type='product' AND p.post_status='publish'
               AND m.demo_audio != ''"
        );
    }

    /**
     * Limpa o cache em memória de get_midis_by_products().
     */
    public static function flush_midi_cache() {
        self::$midi_cache = array();
    }

    /**
     * Produtos publicados que compartilham a mesma URL de demo.
     *
     * Uma única consulta com GROUP BY, porque o duplicatário varre o catálogo
     * inteiro. Os aliases post_id/meta_value são mantidos para não quebrar quem
     * já consumia o formato antigo (postmeta).
     *
     * @return array[] linhas com post_id, post_title, meta_value
     */
    public static function get_duplicated_demo_urls() {
        global $wpdb;

        $table = self::table_name();

        return (array) $wpdb->get_results(
            "SELECT m.product_id AS post_id, p.post_title, m.demo_audio AS meta_value
             FROM {$table} m
             JOIN {$wpdb->posts} p ON p.ID = m.product_id
             JOIN (
                 SELECT demo_audio
                 FROM {$table}
                 WHERE demo_audio != ''
                 GROUP BY demo_audio
                 HAVING COUNT(*) > 1
             ) dup ON m.demo_audio = dup.demo_audio
             WHERE p.post_type='product' AND p.post_status='publish'
             ORDER BY m.demo_audio, p.ID"
        );
    }

    /**
     * Resolve media URL (MP3 demo or MIDI file).
     * Handles:
     * 1. Full external URLs (http:// or https://)
     * 2. Relative paths (/midis/8/2026/file.mp3)
     * 3. Bare filenames (file.mp3) -> mapped to /midis/<mes>/<ano>/file.mp3
     */
    public static function resolve_media_url($value, $mes = 0, $ano = 0) {
        if (empty($value)) {
            return '';
        }
        $value = trim($value);

        // Absolute URL
        if (preg_match('/^https?:\/\//i', $value)) {
            return esc_url($value);
        }

        // Relative path containing slashes
        if (strpos($value, '/') !== false) {
            return esc_url(home_url('/' . ltrim($value, '/')));
        }

        // Bare filename -> mapped to /midis/<YYYYMM>/<file>
        $mes = $mes ? (int) $mes : (int) date('n');
        $ano = $ano ? (int) $ano : (int) date('Y');
        $mes_pad = str_pad($mes, 2, '0', STR_PAD_LEFT);
        $folder_yyyymm = "{$ano}{$mes_pad}";

        return esc_url(home_url("/midis/{$folder_yyyymm}/" . $value));
    }

    /**
     * Get resolved demo audio URL for a product ID.
     *
     * Lê a coluna demo_audio (e mês/ano para montar o caminho de arquivo) da
     * tabela. Não toca em postmeta.
     */
    public static function get_product_demo_url($product_id) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT demo_audio, mes_lancamento, ano_lancamento
             FROM " . self::table_name() . "
             WHERE product_id = %d",
            (int) $product_id
        ));

        if (!$row || '' === (string) $row->demo_audio) {
            return '';
        }

        return self::resolve_media_url(
            $row->demo_audio,
            (int) $row->mes_lancamento,
            (int) $row->ano_lancamento
        );
    }
}