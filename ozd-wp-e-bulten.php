<?php
/**
 * Plugin Name: OZD WP E-Bülten
 * Plugin URI: https://www.sadikozdogan.com
 * Description: WordPress standartlarına uygun, AJAX destekli, ayarlanabilir temel e-bülten abonelik eklentisi.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Sadık Özdoğan
 * Author URI: https://www.sadikozdogan.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ozd-wp-e-bulten
 * Domain Path: /languages
 *
 * @package OZD_WP_EBulten
 */

/**
 * Copyright (C) 2026 Sadık Özdoğan.
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License, version 2 or later.
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'OZD_EBULTEN_VERSION', '1.0.0' );
define( 'OZD_EBULTEN_FILE', __FILE__ );
define( 'OZD_EBULTEN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OZD_EBULTEN_URL', plugin_dir_url( __FILE__ ) );
define( 'OZD_EBULTEN_TABLE', 'ozd_bulten_subs' );
define( 'OZD_EBULTEN_OPTION', 'ozd_ebulten_settings' );
define( 'OZD_EBULTEN_DB_VERSION_OPTION', 'ozd_ebulten_db_version' );
define( 'OZD_EBULTEN_MIN_PHP', '7.4' );

if ( version_compare( PHP_VERSION, OZD_EBULTEN_MIN_PHP, '<' ) ) {
    add_action(
        'admin_notices',
        static function () {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'OZD WP E-Bülten için PHP 7.4 veya üzeri gereklidir.', 'ozd-wp-e-bulten' ) . '</p></div>';
        }
    );
    return;
}

require_once OZD_EBULTEN_DIR . 'includes/class-ozd-ebulten-activator.php';
require_once OZD_EBULTEN_DIR . 'includes/class-ozd-ebulten-helpers.php';
require_once OZD_EBULTEN_DIR . 'public/class-ozd-ebulten-public.php';
require_once OZD_EBULTEN_DIR . 'admin/class-ozd-ebulten-subscribers-table.php';
require_once OZD_EBULTEN_DIR . 'admin/class-ozd-ebulten-admin.php';
require_once OZD_EBULTEN_DIR . 'includes/class-ozd-ebulten-widget.php';
require_once OZD_EBULTEN_DIR . 'includes/class-ozd-ebulten-plugin.php';

register_activation_hook( __FILE__, array( 'OZD_EBulten_Activator', 'activate' ) );

/**
 * Eklentinin ana örneğini döndürür.
 *
 * @return OZD_EBulten_Plugin
 */
function ozd_ebulten_plugin() {
    static $plugin = null;

    if ( null === $plugin ) {
        $plugin = new OZD_EBulten_Plugin();
    }

    return $plugin;
}

ozd_ebulten_plugin()->run();
