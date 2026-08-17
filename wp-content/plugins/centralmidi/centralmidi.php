<?php
/**
 * Plugin Name: Central MIDI
 * Plugin URI: https://centralmidi.com.br
 * Description: Catálogo de MIDIs com classificação #M/#L/#RLM, metadados (artista, gênero, mês de lançamento) e tabelas próprias no banco. Integra com produtos WooCommerce.
 * Version: 1.1.4
 * Author: Central MIDI
 * Text Domain: centralmidi
 * Requires Plugins: woocommerce
 */

defined('ABSPATH') || exit;

define('CENTRALMIDI_VERSION', '1.1.4');
define('CENTRALMIDI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CENTRALMIDI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CENTRALMIDI_TABLE', 'centralmidi_midis');
define('CENTRALMIDI_ARTISTAS_TABLE', 'centralmidi_artistas');
define('CENTRALMIDI_GENEROS_TABLE', 'centralmidi_generos');

require_once CENTRALMIDI_PLUGIN_DIR . 'includes/class-centralmidi-db.php';
require_once CENTRALMIDI_PLUGIN_DIR . 'includes/class-centralmidi-admin.php';
require_once CENTRALMIDI_PLUGIN_DIR . 'includes/class-centralmidi-catalog.php';

/**
 * Fired on activation: create the custom tables.
 */
function centralmidi_activate() {
    CentralMidi_DB::create_table();
    CentralMidi_DB::create_artistas_table();
    CentralMidi_DB::create_generos_table();
}
register_activation_hook(__FILE__, 'centralmidi_activate');

/**
 * Bootstrap the admin + catalog classes and apply schema upgrades.
 */
function centralmidi_init() {
    CentralMidi_DB::maybe_upgrade();
    new CentralMidi_Admin();
    new CentralMidi_Catalog();

    foreach (array('save_post_page', 'wp_trash_post', 'untrash_post', 'delete_post') as $hook) {
        add_action($hook, 'centralmidi_refresh_catalog_url_cache_on_page_change');
    }
}

/**
 * Invalidate the cached catalog page ID whenever a page changes.
 */
function centralmidi_refresh_catalog_url_cache_on_page_change($post_id) {
    if ('page' === get_post_type($post_id)) {
        CentralMidi_DB::refresh_catalog_url_cache();
    }
}
add_action('plugins_loaded', 'centralmidi_init');

/**
 * Uninstall: drop the custom table.
 */
function centralmidi_uninstall() {
    CentralMidi_DB::drop_table();
}
register_uninstall_hook(__FILE__, 'centralmidi_uninstall');