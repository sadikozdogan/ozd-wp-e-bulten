<?php
/**
 * Main plugin bootstrap.
 *
 * @package OZD_WP_EBulten
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
class OZD_EBulten_Plugin {
    /**
     * Public-facing handler.
     *
     * @var OZD_EBulten_Public
     */
    private $public;

    /**
     * Admin-facing handler.
     *
     * @var OZD_EBulten_Admin
     */
    private $admin;

    /** Constructor. */
    public function __construct() {
        $this->public = new OZD_EBulten_Public();
        $this->admin  = new OZD_EBulten_Admin();
    }

    /** Registers plugin hooks. */
    public function run() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ) );

        $this->public->hooks();

        if ( is_admin() ) {
            $this->admin->hooks();
        }

        add_action(
            'widgets_init',
            static function () {
                register_widget( 'OZD_EBulten_Widget' );
            }
        );

        do_action( 'ozd_ebulten_loaded', $this );
    }

    /** Loads textdomain. */
    public function load_textdomain() {
        load_plugin_textdomain( 'ozd-wp-e-bulten', false, dirname( plugin_basename( OZD_EBULTEN_FILE ) ) . '/languages' );
    }

    /** Runs database upgrade when needed. */
    public function maybe_upgrade() {
        $db_version = get_option( OZD_EBULTEN_DB_VERSION_OPTION );

        if ( OZD_EBULTEN_VERSION !== $db_version ) {
            OZD_EBulten_Activator::activate();
        }
    }

    /**
     * Renders frontend form.
     *
     * @param array $args Arguments.
     * @return string
     */
    public function render_form( $args = array() ) {
        return $this->public->render_form( $args );
    }
}
