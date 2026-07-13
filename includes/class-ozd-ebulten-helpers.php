<?php
/**
 * Helper functions.
 *
 * @package OZD_WP_EBulten
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared helper methods.
 */
class OZD_EBulten_Helpers {
    /**
     * Returns merged plugin settings.
     *
     * @return array
     */
    public static function settings() {
        $defaults = OZD_EBulten_Activator::default_settings();
        $saved    = get_option( OZD_EBULTEN_OPTION, array() );
        $settings = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );

        return apply_filters( 'ozd_ebulten_settings', $settings );
    }

    /**
     * Converts common truthy values to boolean.
     *
     * @param mixed $value Value to check.
     * @return bool
     */
    public static function bool( $value ) {
        return '1' === $value || 1 === $value || true === $value || 'yes' === $value;
    }

    /**
     * Returns a validated visitor IP address.
     *
     * @return string
     */
    public static function get_ip() {
        $keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

        foreach ( $keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
            $ip    = trim( $parts[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Returns current URL.
     *
     * @return string
     */
    public static function current_url() {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
        $uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        return esc_url_raw( $scheme . $host . $uri );
    }

    /**
     * Returns sanitized user agent.
     *
     * @return string
     */
    public static function user_agent() {
        return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_textarea_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    }

    /**
     * Checks and increments rate limit counter.
     *
     * @param string $email Email address.
     * @return bool
     */
    public static function rate_limited( $email = '' ) {
        $settings = self::settings();
        $limit    = max( 1, absint( $settings['rate_limit_count'] ?? 5 ) );
        $minutes  = max( 5, absint( $settings['rate_limit_minutes'] ?? 60 ) );
        $key      = 'ozd_ebulten_rate_' . md5( self::get_ip() . '|' . strtolower( $email ) );
        $attempts = (int) get_transient( $key );

        if ( $attempts >= $limit ) {
            return true;
        }

        set_transient( $key, $attempts + 1, $minutes * MINUTE_IN_SECONDS );
        return false;
    }

    /**
     * Generates a token.
     *
     * @return string
     */
    public static function token() {
        return wp_hash( wp_generate_password( 32, true, true ) . microtime( true ) . wp_rand() );
    }

    /**
     * Builds public action URL.
     *
     * @param string $action Action key.
     * @param string $token  Token.
     * @return string
     */
    public static function action_url( $action, $token ) {
        return add_query_arg(
            array(
                'ozd_ebulten_action' => sanitize_key( $action ),
                'token'              => rawurlencode( (string) $token ),
            ),
            home_url( '/' )
        );
    }

    /**
     * Replaces mail merge tags.
     *
     * @param string       $text       Text.
     * @param object|array $subscriber Subscriber data.
     * @param array        $extra      Extra tags.
     * @return string
     */
    public static function replace_tags( $text, $subscriber, $extra = array() ) {
        $name  = is_object( $subscriber ) ? ( ! empty( $subscriber->name ) ? $subscriber->name : '' ) : ( $subscriber['name'] ?? '' );
        $email = is_object( $subscriber ) ? ( ! empty( $subscriber->email ) ? $subscriber->email : '' ) : ( $subscriber['email'] ?? '' );
        $tags  = array_merge(
            array(
                '{site_name}' => get_bloginfo( 'name' ),
                '{name}'      => $name ? $name : __( 'Merhaba', 'ozd-wp-e-bulten' ),
                '{email}'     => $email,
            ),
            $extra
        );

        return apply_filters( 'ozd_ebulten_replace_tags', strtr( $text, $tags ), $subscriber, $tags );
    }

    /**
     * Returns wp_mail headers.
     *
     * @return array
     */
    public static function mail_headers() {
        $settings   = self::settings();
        $from_name  = sanitize_text_field( $settings['sender_name'] );
        $from_email = sanitize_email( $settings['sender_email'] );
        $headers    = array();

        if ( $from_email && is_email( $from_email ) ) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        return apply_filters( 'ozd_ebulten_mail_headers', $headers );
    }

    /**
     * Returns allowed subscriber statuses.
     *
     * @return array
     */
    public static function allowed_statuses() {
        return apply_filters(
            'ozd_ebulten_allowed_statuses',
            array(
                'pending'      => __( 'Onay Bekliyor', 'ozd-wp-e-bulten' ),
                'confirmed'    => __( 'Onaylandı', 'ozd-wp-e-bulten' ),
                'unsubscribed' => __( 'Abonelikten Çıktı', 'ozd-wp-e-bulten' ),
            )
        );
    }

    /**
     * Returns a template path with theme override support.
     *
     * @param string $template Template filename.
     * @return string
     */
    public static function template_path( $template ) {
        $template = ltrim( $template, '/\\' );
        $theme    = locate_template( 'ozd-wp-e-bulten/' . $template );

        if ( $theme ) {
            return apply_filters( 'ozd_ebulten_template_path', $theme, $template );
        }

        return apply_filters( 'ozd_ebulten_template_path', OZD_EBULTEN_DIR . 'templates/' . $template, $template );
    }
}
