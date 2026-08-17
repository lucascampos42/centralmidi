<?php
/**
 * Homepage Template - Central Midi
 * Shows:
 * 1. Current Month Releases (up to 30)
 * 2. Previous Month Releases (up to 30)
 * 3. 2 Months Ago Releases (up to 30)
 */
get_header();

get_template_part('template-parts/carousel');

// Determine 3 latest months from DB or calendar
$cm_db_available = class_exists('CentralMidi_DB');
$db_months = $cm_db_available ? CentralMidi_DB::distinct('mes_lancamento') : array();
rsort($db_months);

$current_calendar_month = (int) date('n');
$m1 = !empty($db_months[0]) ? (int)$db_months[0] : $current_calendar_month;
$m2 = !empty($db_months[1]) ? (int)$db_months[1] : (($current_calendar_month - 1 < 1) ? 12 : $current_calendar_month - 1);
$m3 = !empty($db_months[2]) ? (int)$db_months[2] : (($current_calendar_month - 2 < 1) ? (12 + ($current_calendar_month - 2)) : $current_calendar_month - 2);

$featured_months = array(
    array(
        'mes'      => $m1,
        'badge'    => 'Lançamentos do Mês',
        'icon'     => 'ri-fire-fill',
        'subtitle' => 'Músicas recém-adicionadas e novidades para o seu repertório.',
    ),
    array(
        'mes'      => $m2,
        'badge'    => 'Lançamentos do Mês Anterior',
        'icon'     => 'ri-sparkling-2-fill',
        'subtitle' => 'Sucessos lançados no mês passado disponíveis para download.',
    ),
    array(
        'mes'      => $m3,
        'badge'    => 'Lançamentos Recentes',
        'icon'     => 'ri-box-3-fill',
        'subtitle' => 'Confira também as faixas lançadas há 2 meses.',
    ),
);

// Cache monthly release data (6h) so the homepage is stable and fast.
$cache_key = 'centralmidi_home_' . implode('_', array($m1, $m2, $m3));
$month_data = get_transient($cache_key);

if (false === $month_data) {
    $month_data = array();
    foreach ($featured_months as $month_config) {
        $mes_num = $month_config['mes'];
        $total_mes = $cm_db_available ? CentralMidi_DB::count_by_month($mes_num) : 0;
        $product_ids = $cm_db_available ? CentralMidi_DB::get_midis_by_month($mes_num, 30) : array();

        $month_data[] = array(
            'mes'          => $mes_num,
            'mes_nome'     => $cm_db_available ? CentralMidi_DB::mes_nome($mes_num) : '',
            'total'        => $total_mes,
            'product_ids'  => $product_ids,
            'badge'        => $month_config['badge'],
            'icon'         => $month_config['icon'],
            'subtitle'     => $month_config['subtitle'],
        );
    }
    set_transient($cache_key, $month_data, 6 * HOUR_IN_SECONDS);
}
?>

<div class="cm-container cm-home-releases">
    <?php foreach ($month_data as $index => $release) : ?>
        <section class="cm-month-section<?php echo ($index > 0) ? ' cm-month-section--bordered' : ''; ?>">
            <div class="cm-section-header">
                <div>
                    <span class="cm-badge"><i class="<?php echo esc_attr($release['icon']); ?>"></i> <?php echo esc_html($release['badge']); ?></span>
                    <h2 class="cm-section-title">Lançamentos de <?php echo esc_html($release['mes_nome']); ?></h2>
                    <p class="cm-section-subtitle"><?php echo esc_html($release['subtitle']); ?></p>
                </div>

                <div>
                    <a href="<?php echo esc_url(add_query_arg('mes_lancamento', $release['mes'], centralmidi_catalog_url())); ?>" class="cm-btn cm-btn-outline">
                        Ver todos de <?php echo esc_html($release['mes_nome']); ?> (<?php echo esc_html($release['total']); ?>) <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>

            <?php if (!empty($release['product_ids'])) : ?>
                <div class="cm-tracks-grid">
                    <?php foreach ($release['product_ids'] as $pid) : ?>
                        <?php get_template_part('template-parts/card-midi', null, array('product_id' => $pid)); ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="centralmidi-empty cm-empty-state">
                    <i class="ri-music-2-line"></i>
                    <p>Nenhum lançamento cadastrado para o mês de <?php echo esc_html($release['mes_nome']); ?> ainda.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<?php get_footer(); ?>