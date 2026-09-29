<?php
/**
 * Leitura em lote dos dados de catálogo para o front-end.
 *
 * @package CentralMidi
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adapter de leitura para os temas que já rodavam sobre as taxonomias legadas.
 *
 * Antes, o card de produto fazia três leituras por item (gênero, rlm e mês via
 * taxonomia + ACF). Em listagens de centenas de MIDIs isso virava centenas de
 * queries. Aqui o metadado de N produtos sai em uma única query, memoizada por
 * request.
 *
 * Os rótulos deste arquivo NÃO são os de CentralMidi_DB::classificacao_label()
 * nem os de CentralMidi_DB::mes_nome(): o texto exibido na vitrine atual é
 * "Letra | Melodia" e "MAR 2026", e trocar isso altera o visual.
 */
class CentralMidi_Frontend {

    /**
     * Rótulos de classificação da vitrine atual (texto curto das tags do card).
     *
     * @var array<string,string>
     */
    private static $classificacoes = array(
        'RLM' => 'Letra | Melodia',
        'L'   => 'Letra',
        'M'   => 'Melodia',
    );

    /**
     * Abreviações de mês da vitrine atual.
     *
     * Janeiro é a única em caixa mista ("Jan"); as outras onze vieram em
     * maiúsculas da digitação original do ACF. Mantido assim de propósito:
     * normalizar para "Jan"/"Fev" mudaria o texto exibido de 100% dos produtos.
     *
     * @var array<int,string>
     */
    private static $meses = array(
        1  => 'Jan',
        2  => 'FEV',
        3  => 'MAR',
        4  => 'ABR',
        5  => 'MAI',
        6  => 'JUN',
        7  => 'JUL',
        8  => 'AGO',
        9  => 'SET',
        10 => 'OUT',
        11 => 'NOV',
        12 => 'DEZ',
    );

    /**
     * Metadado por product_id, memoizado durante o request.
     *
     * @var array<int,array<string,mixed>|null>
     */
    private static $cache = array();

    /**
     * Texto da tag de classificação, ou string vazia se não houver.
     *
     * @param string $value Código M/L/RLM.
     * @return string
     */
    public static function classificacao_label($value) {
        $code = CentralMidi_DB::sanitize_classificacao($value);
        return $code ? self::$classificacoes[$code] : '';
    }

    /**
     * Rótulo de mês/ano no formato da vitrine atual ("MAR 2026").
     *
     * @param int $mes 1-12.
     * @param int $ano Ano de quatro dígitos.
     * @return string Vazio quando o mês é inválido.
     */
    public static function mes_label($mes, $ano = 0) {
        $mes = absint($mes);
        if (!isset(self::$meses[$mes])) {
            return '';
        }
        $ano = absint($ano);
        return $ano ? self::$meses[$mes] . ' ' . $ano : self::$meses[$mes];
    }

    /**
     * Rótulo de mês no formato do menu lateral da home ("Set-2026").
     *
     * Diferente de mes_label() de propósito: o menu usava o slug do termo
     * legado (`set-2026`) com ucfirst(), e é assim que o texto aparece hoje na
     * tela. Reaproveitar o formato das tags ("SET 2026") mudaria o menu.
     *
     * @param int $mes 1-12.
     * @param int $ano Ano de quatro dígitos.
     * @return string
     */
    public static function mes_label_nav($mes, $ano = 0) {
        $mes = absint($mes);
        if (!isset(self::$meses[$mes])) {
            return '';
        }
        $label = strtolower(self::$meses[$mes]) . '-' . absint($ano);
        return ucfirst($label);
    }

    /**
     * Metadado de um único produto, com fallback seguro.
     *
     * @param int $product_id Product ID.
     * @return array<string,mixed>
     */
    public static function get($product_id) {
        $product_id = absint($product_id);
        if (!$product_id) {
            return self::empty_data();
        }

        if (!array_key_exists($product_id, self::$cache)) {
            self::prime(array($product_id));
        }

        return self::$cache[$product_id] ?: self::empty_data();
    }

    /**
     * Metadado de vários produtos em uma única query.
     *
     * @param int[] $product_ids Product IDs.
     * @return array<int,array<string,mixed>> Indexado por product_id.
     */
    public static function get_many($product_ids) {
        $product_ids = array_values(array_unique(array_filter(array_map('absint', (array) $product_ids))));

        $pending = array();
        foreach ($product_ids as $id) {
            if (!array_key_exists($id, self::$cache)) {
                $pending[] = $id;
            }
        }
        self::prime($pending);

        $out = array();
        foreach ($product_ids as $id) {
            $out[$id] = self::$cache[$id] ?: self::empty_data();
        }
        return $out;
    }

    /**
     * Carrega do banco os product_ids ainda não memoizados.
     *
     * @param int[] $product_ids Product IDs.
     */
    private static function prime($product_ids) {
        global $wpdb;

        $product_ids = array_values(array_filter(array_map('absint', (array) $product_ids)));
        if (!$product_ids) {
            return;
        }

        $midis_table    = CentralMidi_DB::table_name();
        $artistas_table = CentralMidi_DB::artistas_table_name();
        $generos_table  = CentralMidi_DB::generos_table_name();

        // Aquece o cache de postmeta de uma vez. Sem isso, get_product_demo_url()
        // dispara três get_post_meta() por produto e o lote vira N+1 de novo.
        update_meta_cache('post', $product_ids);

        foreach (array_chunk($product_ids, 500) as $chunk) {
            $in = implode(',', array_map('intval', $chunk));
            $sql = "SELECT m.product_id, m.mes_lancamento, m.ano_lancamento,
                           m.classificacao, m.publicado,
                           a.nome AS artista_nome,
                           g.nome AS genero_nome
                      FROM {$midis_table} AS m
                 LEFT JOIN {$artistas_table} AS a ON a.id = m.artista_id
                 LEFT JOIN {$generos_table}  AS g ON g.id = m.genero_id
                     WHERE m.product_id IN ({$in})";

            $rows = $wpdb->get_results($sql); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- IDs are intval'd above.

            $found = array();
            foreach ((array) $rows as $row) {
                $pid      = absint($row->product_id);
                $mes      = absint($row->mes_lancamento);
                $ano      = absint($row->ano_lancamento);
                $found[$pid] = array(
                    'artista_nome'   => (string) $row->artista_nome,
                    'genero_nome'    => (string) $row->genero_nome,
                    'mes'            => $mes,
                    'ano'            => $ano,
                    'mes_label'      => self::mes_label($mes, $ano),
                    'classificacao'  => (string) $row->classificacao,
                    'rotulo'         => self::classificacao_label($row->classificacao),
                    'publicado'      => absint($row->publicado),
                    'demo_url'       => CentralMidi_DB::get_product_demo_url($pid),
                );
            }

            // Marca como vistos mesmo sem linha na tabela, para não repetir a query.
            foreach ($chunk as $pid) {
                self::$cache[$pid] = $found[$pid] ?? null;
            }
        }
    }

    /**
     * Estrutura vazia, com as mesmas chaves de um registro real.
     *
     * @return array<string,mixed>
     */
    private static function empty_data() {
        return array(
            'artista_nome'  => '',
            'genero_nome'   => '',
            'mes'           => 0,
            'ano'           => 0,
            'mes_label'     => '',
            'classificacao' => '',
            'rotulo'        => '',
            'publicado'     => 0,
            'demo_url'      => '',
        );
    }

    /**
     * Esquece a memoização do request. Usado pelos testes e pelo admin.
     */
    public static function flush() {
        self::$cache = array();
    }
}
