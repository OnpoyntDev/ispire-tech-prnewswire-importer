<?php
/**
 * Plugin Name: Ispire Tech PR Newswire Importer
 * Plugin URI: https://ispiretechnology.com/
 * Description: Imports PR Newswire RSS releases into WordPress posts.
 * Version: 1.0.0
 * Author: Onpoynt Creative
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin constants
 */
define(
    'PRN_IMPORTER_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'PRN_IMPORTER_URL',
    plugin_dir_url(__FILE__)
);

/**
 * Load classes
 */
require_once PRN_IMPORTER_PATH . 'includes/class-prn-importer.php';

require_once PRN_IMPORTER_PATH . 'includes/class-prn-cron.php';

require_once PRN_IMPORTER_PATH . 'includes/class-prn-media.php';

require_once PRN_IMPORTER_PATH . 'includes/class-prn-taxonomy.php';

require_once PRN_IMPORTER_PATH . 'includes/class-prn-logger.php';

/**
 * Activation hook
 */
register_activation_hook(
    __FILE__,
    ['PRN_Cron', 'activate']
);

/**
 * Deactivation hook
 */
register_deactivation_hook(
    __FILE__,
    ['PRN_Cron', 'deactivate']
);

/**
 * Cron action
 */
add_action(
    'prn_import_cron',
    ['PRN_Importer', 'run']
);

/**
 * Manual admin trigger
 *
 * URL:
 * /wp-admin/?prn-run-import=1
 */
add_action('admin_init', function () {

    if (!current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['prn-run-import'])) {
        return;
    }

    PRN_Importer::run();

    wp_die('PR Newswire import completed.');
});