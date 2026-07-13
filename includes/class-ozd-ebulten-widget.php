<?php
/**
 * Legacy widget support.
 *
 * @package OZD_WP_EBulten
 */

defined( 'ABSPATH' ) || exit;

/**
 * Newsletter form widget.
 */
class OZD_EBulten_Widget extends WP_Widget {
    /** Constructor. */
    public function __construct() {
        parent::__construct(
            'ozd_e_bulten_widget',
            __( 'OZD E-Bülten', 'ozd-wp-e-bulten' ),
            array( 'description' => __( 'OZD e-bülten abonelik formu.', 'ozd-wp-e-bulten' ) )
        );
    }

    /**
     * Outputs widget.
     *
     * @param array $args     Widget args.
     * @param array $instance Widget instance.
     */
    public function widget( $args, $instance ) {
        echo wp_kses_post( $args['before_widget'] ?? '' );

        if ( ! empty( $instance['title'] ) ) {
            echo wp_kses_post( $args['before_title'] ?? '<h4>' ) . esc_html( $instance['title'] ) . wp_kses_post( $args['after_title'] ?? '</h4>' );
        }

        echo wp_kses_post( ozd_ebulten_plugin()->render_form() );
        echo wp_kses_post( $args['after_widget'] ?? '' );
    }

    /**
     * Widget form.
     *
     * @param array $instance Widget instance.
     */
    public function form( $instance ) {
        $title = $instance['title'] ?? __( 'E-Bülten', 'ozd-wp-e-bulten' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Başlık:', 'ozd-wp-e-bulten' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    /**
     * Saves widget options.
     *
     * @param array $new_instance New instance.
     * @param array $old_instance Old instance.
     * @return array
     */
    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );

        return $instance;
    }
}
