<?php
/**
 * Central MIDI DB: custom tables for MIDI metadata.
 *
 * Tables:
 *   - wp_centralmidi_midis      (product_id, artista_id, genero_id, categoria_id, mes_lancamento, classificacao)
 *   - wp_centralmidi_artistas   (id, nome)
 *   - wp_centralmidi_generos    (id, nome)
 *   - wp_centralmidi_categorias (id, nome)
 *
 * Classification: M (melody), L (lyrics), RLM (melody + lyrics)
 */

defined('ABSPATH') || exit;

class CentralMidi_DB {

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

    public static function categorias_table_name() {
        global $wpdb;
        return $wpdb->prefix . CENTRALMIDI_CATEGORIAS_TABLE;
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
            categoria_id BIGINT UNSIGNED NULL,
            mes_lancamento TINYINT UNSIGNED NOT NULL DEFAULT 0,
            classificacao VARCHAR(3) NOT NULL DEFAULT 'M',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY product_id (product_id),
            KEY artista_id (artista_id),
            KEY genero_id (genero_id),
            KEY categoria_id (categoria_id),
            KEY mes_lancamento (mes_lancamento),
            KEY classificacao (classificacao)
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

    public static function create_categorias_table() {
        self::create_referencia_table(self::categorias_table_name());
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
     * Run on every load (cheap checks) to apply schema upgrades and data migration.
     */
    public static function maybe_upgrade() {
        global $wpdb;

        $midis_table = self::table_name();

        foreach (array(
            'artista'   => self::artistas_table_name(),
            'genero'    => self::generos_table_name(),
            'categoria' => self::categorias_table_name(),
        ) as $kind => $table) {
            if (!self::table_exists($table)) {
                if ('artista' === $kind) {
                    self::create_artistas_table();
                } elseif ('genero' === $kind) {
                    self::create_generos_table();
                } else {
                    self::create_categorias_table();
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
        foreach (array('artista_id', 'genero_id', 'categoria_id') as $col) {
            if (!self::column_exists($midis_table, $col)) {
                $wpdb->query("ALTER TABLE {$midis_table} ADD COLUMN {$col} BIGINT UNSIGNED NULL AFTER {$after}");
            }
            $after = $col;
        }

        // Migrate legacy denormalized strings to the reference tables.
        self::migrate_legacy_data();

        // Drop the legacy denormalized string columns once migration is done.
        foreach (array('artista', 'genero') as $col) {
            if (self::column_exists($midis_table, $col)) {
                $wpdb->query("ALTER TABLE {$midis_table} DROP COLUMN {$col}");
            }
        }
    }

    private static function table_exists($table) {
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
        $wpdb->query("DROP TABLE IF EXISTS " . self::categorias_table_name());
    }

    /**
     * Upsert one row for a product.
     *
     * @param int $product_id
     * @param array $data Keys: artista_id, genero_id, categoria_id, mes_lancamento, classificacao
     */
    public static function upsert($product_id, $data) {
        global $wpdb;

        $table_name = self::table_name();
        $now        = current_time('mysql');

        $artista_id = isset($data['artista_id']) ? absint($data['artista_id']) : 0;
        $genero_id  = isset($data['genero_id']) ? absint($data['genero_id']) : 0;
        $categoria_id = isset($data['categoria_id']) ? absint($data['categoria_id']) : 0;

        // Resolve reference IDs from legacy names when only the name was given.
        if (!$artista_id && !empty($data['artista'])) {
            $artista_id = self::add_artista($data['artista']);
        }
        if (!$genero_id && !empty($data['genero'])) {
            $genero_id = self::add_genero($data['genero']);
        }
        if (!$categoria_id && !empty($data['categoria'])) {
            $categoria_id = self::add_categoria($data['categoria']);
        }

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM {$table_name} WHERE product_id = %d", $product_id)
        );

        $payload = array(
            'product_id'     => (int) $product_id,
            'artista_id'     => $artista_id ? $artista_id : null,
            'genero_id'      => $genero_id ? $genero_id : null,
            'categoria_id'   => $categoria_id ? $categoria_id : null,
            'mes_lancamento' => absint($data['mes_lancamento'] ?? 0),
            'classificacao'  => self::sanitize_classificacao($data['classificacao'] ?? 'M'),
            'updated_at'     => $now,
        );

        if ($row) {
            $wpdb->update($table_name, $payload, array('id' => $row->id));
        } else {
            $payload['created_at'] = $now;
            $wpdb->insert($table_name, $payload);
        }
    }

    /**
     * Delete a row for a product.
     */
    public static function delete($product_id) {
        global $wpdb;
        $wpdb->delete(self::table_name(), array('product_id' => (int) $product_id));
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
                LEFT JOIN {$midis_table} m ON (m.artista_id = a.id)
                WHERE " . implode(' AND ', $where) . "
                GROUP BY a.id, a.nome, a.foto_id
                ORDER BY a.nome ASC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
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
     * Categorias
     * ---------------------------------------------------------------- */

    public static function get_categorias() {
        return self::get_referencias(self::categorias_table_name());
    }

    public static function get_categoria($id) {
        return self::get_referencia(self::categorias_table_name(), $id);
    }

    public static function get_categoria_by_nome($nome) {
        return self::get_referencia_by_nome(self::categorias_table_name(), $nome);
    }

    public static function add_categoria($nome) {
        return self::add_referencia(self::categorias_table_name(), $nome);
    }

    public static function update_categoria($id, $nome) {
        return self::update_referencia(self::categorias_table_name(), $id, $nome);
    }

    public static function delete_categoria($id) {
        return self::delete_referencia(self::categorias_table_name(), 'categoria_id', $id);
    }

    public static function categorias_count() {
        return self::referencias_count(self::categorias_table_name());
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
        return in_array($value, array('M', 'L', 'RLM'), true) ? $value : 'M';
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
     * @param string $column artista|genero|categoria|mes_lancamento|classificacao
     * @return array
     */
    public static function distinct($column) {
        global $wpdb;

        $midis_table = self::table_name();

        if ('artista' === $column) {
            $table = self::artistas_table_name();
            $sql   = "SELECT DISTINCT a.nome AS val FROM {$midis_table} m INNER JOIN {$table} a ON a.id = m.artista_id WHERE a.nome <> '' ORDER BY a.nome ASC";
            return $wpdb->get_col($sql);
        }

        if ('genero' === $column) {
            $table = self::generos_table_name();
            $sql   = "SELECT DISTINCT g.nome AS val FROM {$midis_table} m INNER JOIN {$table} g ON g.id = m.genero_id WHERE g.nome <> '' ORDER BY g.nome ASC";
            return $wpdb->get_col($sql);
        }

        if ('categoria' === $column) {
            $table = self::categorias_table_name();
            $sql   = "SELECT DISTINCT c.nome AS val FROM {$midis_table} m INNER JOIN {$table} c ON c.id = m.categoria_id WHERE c.nome <> '' ORDER BY c.nome ASC";
            return $wpdb->get_col($sql);
        }

        $allowed = array('mes_lancamento', 'classificacao');
        if (!in_array($column, $allowed, true)) {
            return array();
        }

        $query   = "SELECT DISTINCT {$column} AS val FROM {$midis_table} WHERE {$column} <> '' ORDER BY val ASC";
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
            "SELECT product_id FROM {$table_name} WHERE mes_lancamento = %d ORDER BY RAND() LIMIT %d",
            absint($month),
            absint($limit)
        );
        return array_map('intval', $wpdb->get_col($sql));
    }

    /**
     * Get product IDs for a specific launch month (up to $limit), newest first.
     * Deterministic ordering (no RAND()) so pages are stable and cacheable.
     */
    public static function get_midis_by_month($month, $limit = 30) {
        global $wpdb;
        $table_name = self::table_name();
        $sql = $wpdb->prepare(
            "SELECT product_id FROM {$table_name} WHERE mes_lancamento = %d ORDER BY created_at DESC, product_id DESC LIMIT %d",
            absint($month),
            absint($limit)
        );
        return array_map('intval', $wpdb->get_col($sql));
    }

    /**
     * Total count of MIDIs for a given month.
     */
    public static function count_by_month($month) {
        global $wpdb;
        $table_name = self::table_name();
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE mes_lancamento = %d",
            absint($month)
        );
        return (int) $wpdb->get_var($sql);
    }

    /**
     * Returns product IDs matching all given filters (AND).
     *
     * @param array $filters Keys: artista, genero, categoria, mes_lancamento, classificacao
     * @return array|int[] product IDs
     */
    public static function search_product_ids($filters = array()) {
        global $wpdb;

        $midis_table    = self::table_name();
        $artistas_table = self::artistas_table_name();
        $generos_table  = self::generos_table_name();
        $categorias_table = self::categorias_table_name();

        $joins  = array();
        $where  = array('1=1');
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

        if (!empty($filters['categoria'])) {
            $joins[]  = "INNER JOIN {$categorias_table} c ON c.id = m.categoria_id";
            $where[]  = "c.nome = %s";
            $params[] = $filters['categoria'];
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
     * Search MIDIs by artist, genre or category name (LIKE).
     *
     * @param string $term Search term.
     * @return int[] product IDs
     */
    public static function search_by_term($term) {
        global $wpdb;

        $midis_table    = self::table_name();
        $artistas_table = self::artistas_table_name();
        $generos_table  = self::generos_table_name();
        $categorias_table = self::categorias_table_name();

        $like = '%' . $wpdb->esc_like(trim($term)) . '%';
        $sql = $wpdb->prepare(
            "SELECT DISTINCT m.product_id
             FROM {$midis_table} m
             LEFT JOIN {$artistas_table} a ON a.id = m.artista_id
             LEFT JOIN {$generos_table} g ON g.id = m.genero_id
             LEFT JOIN {$categorias_table} c ON c.id = m.categoria_id
             WHERE a.nome LIKE %s OR g.nome LIKE %s OR c.nome LIKE %s",
            $like,
            $like,
            $like
        );
        return array_map('intval', $wpdb->get_col($sql));
    }
}