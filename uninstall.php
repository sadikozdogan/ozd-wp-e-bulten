<?php
/**
 * Plugin uninstall routine.
 *
 * @package OZD_WP_EBulten
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'ozd_ebulten_settings', array() );

if ( is_array( $settings ) && ! empty( $settings['cleanup_on_uninstall'] ) ) {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Optional uninstall cleanup.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ozd_bulten_subs" );
    delete_option( 'ozd_ebulten_settings' );
    delete_option( 'ozd_ebulten_db_version' );
}
