<?php
/**
 * Public-facing functionality.
 *
 * @package OZD_WP_EBulten
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles shortcode, form submission and public actions.
 */
class OZD_EBulten_Public {
    /** Nonce action. */
    const NONCE_ACTION = 'ozd_ebulten_submit';

    /** Registers public hooks. */
    public function hooks() {
        add_shortcode( 'ozd_e_bulten', array( $this, 'shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_ozd_ebulten_subscribe', array( $this, 'ajax_subscribe' ) );
        add_action( 'wp_ajax_nopriv_ozd_ebulten_subscribe', array( $this, 'ajax_subscribe' ) );
        add_action( 'admin_post_ozd_ebulten_subscribe', array( $this, 'post_subscribe' ) );
        add_action( 'admin_post_nopriv_ozd_ebulten_subscribe', array( $this, 'post_subscribe' ) );
        add_action( 'init', array( $this, 'maybe_handle_public_action' ) );
    }

    /** Enqueues public assets. */
    public function enqueue_assets() {
        wp_enqueue_style( 'ozd-ebulten', OZD_EBULTEN_URL . 'assets/css/ozd-bulten.css', array(), OZD_EBULTEN_VERSION );
        wp_enqueue_script( 'ozd-ebulten', OZD_EBULTEN_URL . 'assets/js/ozd-bulten.js', array(), OZD_EBULTEN_VERSION, true );
        wp_localize_script(
            'ozd-ebulten',
            'OZDEBulten',
            array(
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'ajaxEnabled'   => OZD_EBulten_Helpers::settings()['enable_ajax'],
                'defaultError'  => __( 'İşlem tamamlanamadı.', 'ozd-wp-e-bulten' ),
                'defaultDone'   => __( 'İşlem başarılı.', 'ozd-wp-e-bulten' ),
                'networkError'  => __( 'Bağlantı hatası oluştu. Lütfen tekrar deneyin.', 'ozd-wp-e-bulten' ),
            )
        );
    }

    /**
     * Shortcode callback.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcode( $atts = array() ) {
        return $this->render_form( $atts );
    }

    /**
     * Renders subscription form.
     *
     * @param array $args Args.
     * @return string
     */
    public function render_form( $args = array() ) {
        $settings      = OZD_EBulten_Helpers::settings();
        $uid           = wp_rand( 1000, 999999 );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only frontend message from redirect.
        $query_message = isset( $_GET['ozd_ebulten_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['ozd_ebulten_msg'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only frontend message type from redirect.
        $query_type = isset( $_GET['ozd_ebulten_type'] ) && 'success' === $_GET['ozd_ebulten_type'] ? 'success' : 'error';
        $template      = OZD_EBulten_Helpers::template_path( 'form.php' );

        ob_start();
        if ( file_exists( $template ) ) {
            include $template;
        }
        $html = ob_get_clean();

        return apply_filters( 'ozd_ebulten_form_html', $html, $args, $settings );
    }

    /** Handles AJAX subscription. */
    public function ajax_subscribe() {
        $result = $this->handle_request();

        if ( $result['ok'] ) {
            wp_send_json_success( $result );
        }

        wp_send_json_error( $result );
    }

    /** Handles non-AJAX subscription. */
    public function post_subscribe() {
        $result = $this->handle_request();
        $url    = wp_get_referer() ? wp_get_referer() : home_url( '/' );
        $url    = remove_query_arg( array( 'ozd_ebulten_msg', 'ozd_ebulten_type' ), $url );
        $url    = add_query_arg(
            array(
                'ozd_ebulten_msg'  => rawurlencode( $result['message'] ),
                'ozd_ebulten_type' => $result['ok'] ? 'success' : 'error',
            ),
            $url
        );

        wp_safe_redirect( $url );
        exit;
    }

    /** Handles confirmation and unsubscribe links. */
    public function maybe_handle_public_action() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public signed token action link.
        if ( empty( $_GET['ozd_ebulten_action'] ) || empty( $_GET['token'] ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public signed token action link.
        $action = sanitize_key( wp_unslash( $_GET['ozd_ebulten_action'] ) );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public signed token action link.
        $token = sanitize_text_field( wp_unslash( $_GET['token'] ) );

        if ( 'confirm' === $action ) {
            $this->confirm_subscription( $token );
        }

        if ( 'unsubscribe' === $action ) {
            $this->unsubscribe( $token );
        }
    }

    /**
     * Handles form request.
     *
     * @return array
     */
    private function handle_request() {
        do_action( 'ozd_ebulten_before_handle_request' );

        if ( empty( $_POST['ozd_ebulten_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ozd_ebulten_nonce'] ) ), self::NONCE_ACTION ) ) {
            return $this->fail( __( 'Geçersiz istek. Lütfen sayfayı yenileyin.', 'ozd-wp-e-bulten' ) );
        }

        if ( ! empty( $_POST['ozd_website'] ) ) {
            return $this->fail( __( 'İstek reddedildi.', 'ozd-wp-e-bulten' ) );
        }

        $settings = OZD_EBulten_Helpers::settings();
        $step     = isset( $_POST['ozd_step'] ) ? sanitize_text_field( wp_unslash( $_POST['ozd_step'] ) ) : 'email';
        $name     = isset( $_POST['ozd_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ozd_name'] ) ) : '';
        $email    = isset( $_POST['ozd_email'] ) ? sanitize_email( wp_unslash( $_POST['ozd_email'] ) ) : '';

        $request_data = apply_filters(
            'ozd_ebulten_request_data',
            array(
                'step'  => $step,
                'name'  => $name,
                'email' => $email,
            )
        );

        $step  = isset( $request_data['step'] ) ? sanitize_text_field( $request_data['step'] ) : $step;
        $name  = isset( $request_data['name'] ) ? sanitize_text_field( $request_data['name'] ) : $name;
        $email = isset( $request_data['email'] ) ? sanitize_email( $request_data['email'] ) : $email;

        if ( OZD_EBulten_Helpers::bool( $settings['require_name'] ) && OZD_EBulten_Helpers::bool( $settings['show_name_field'] ) && '' === $name ) {
            return $this->fail( __( 'Lütfen ad soyad alanını doldurun.', 'ozd-wp-e-bulten' ) );
        }

        if ( ! $email || ! is_email( $email ) ) {
            return $this->fail( __( 'Lütfen geçerli bir e-posta adresi giriniz.', 'ozd-wp-e-bulten' ) );
        }

        if ( OZD_EBulten_Helpers::rate_limited( $email ) ) {
            return $this->fail( __( 'Çok fazla deneme yaptınız. Lütfen daha sonra tekrar deneyin.', 'ozd-wp-e-bulten' ) );
        }

        if ( 'email' === $step ) {
            return array(
                'ok'         => true,
                'step'       => 'consent',
                'message'    => __( 'Lütfen abonelik onay metnini okuyup onaylayın.', 'ozd-wp-e-bulten' ),
                'buttonText' => $settings['confirm_button_text'],
            );
        }

        $consent = ! empty( $_POST['ozd_consent'] ) ? 1 : 0;
        if ( ! $consent ) {
            return $this->fail( __( 'Lütfen onay metnini kabul edin.', 'ozd-wp-e-bulten' ) );
        }

        global $wpdb;

        $table  = $wpdb->prefix . OZD_EBULTEN_TABLE;
        $now    = current_time( 'mysql' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table lookup.
        $exists = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE email = %s', $table, $email ) );

        if ( $exists && 'confirmed' === $exists->status ) {
            return $this->fail( $settings['already_message'] );
        }

        if ( $exists && 'pending' === $exists->status && OZD_EBulten_Helpers::bool( $settings['enable_double_optin'] ) ) {
            $sent = $this->send_confirmation_email( $exists );

            if ( ! $sent ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Store mail sending failure.
                $wpdb->update(
                    $table,
                    array(
                        'last_error' => __( 'Onay e-postası tekrar gönderilemedi.', 'ozd-wp-e-bulten' ),
                        'updated_at' => $now,
                    ),
                    array( 'id' => (int) $exists->id )
                );

                return $this->fail( __( 'Onay e-postası gönderilemedi. Lütfen site yöneticisiyle iletişime geçin.', 'ozd-wp-e-bulten' ) );
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clear previous mail sending failure after a successful resend.
            $wpdb->update(
                $table,
                array(
                    'last_error' => null,
                    'updated_at' => $now,
                ),
                array( 'id' => (int) $exists->id )
            );

            return array( 'ok' => true, 'step' => 'done', 'message' => $settings['pending_already_message'] );
        }

        $token  = OZD_EBulten_Helpers::token();
        $status = OZD_EBulten_Helpers::bool( $settings['enable_double_optin'] ) ? 'pending' : 'confirmed';
        $data   = array(
            'name'                 => $name,
            'email'                => $email,
            'ip_address'           => OZD_EBulten_Helpers::get_ip(),
            'user_agent'           => OZD_EBulten_Helpers::user_agent(),
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified before reading form data.
            'source_url'           => isset( $_POST['ozd_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['ozd_source_url'] ) ) : OZD_EBulten_Helpers::current_url(),
            'status'               => $status,
            'consent'              => 1,
            'consent_text_version' => sanitize_text_field( $settings['consent_version'] ),
            'confirmation_token'   => $token,
            'token_created_at'     => $now,
            'confirmed_at'         => 'confirmed' === $status ? $now : null,
            'unsubscribed_at'      => null,
            'last_error'           => null,
            'updated_at'           => $now,
        );

        $data = apply_filters( 'ozd_ebulten_subscriber_data', $data, $exists );
        do_action( 'ozd_ebulten_before_save_subscriber', $data, $exists );

        if ( $exists ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table write.
            $wpdb->update( $table, $data, array( 'id' => (int) $exists->id ) );
            $subscriber_id = (int) $exists->id;
        } else {
            $data['created_at'] = $now;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table write.
            $wpdb->insert( $table, $data );
            $subscriber_id = (int) $wpdb->insert_id;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fetch saved subscriber row.
        $subscriber = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $subscriber_id ) );
        do_action( 'ozd_ebulten_after_save_subscriber', $subscriber, $exists );

        if ( 'pending' === $status ) {
            $sent = $this->send_confirmation_email( $subscriber );
            if ( ! $sent ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Store mail sending failure.
                $wpdb->update(
                    $table,
                    array(
                        'last_error' => __( 'Onay e-postası gönderilemedi.', 'ozd-wp-e-bulten' ),
                        'updated_at' => $now,
                    ),
                    array( 'id' => $subscriber_id )
                );
                return $this->fail( __( 'Kayıt alındı ancak onay e-postası gönderilemedi. Lütfen site yöneticisiyle iletişime geçin.', 'ozd-wp-e-bulten' ) );
            }
            $result = array( 'ok' => true, 'step' => 'done', 'message' => $settings['pending_message'] );
            return apply_filters( 'ozd_ebulten_subscribe_result', $result, $subscriber );
        }

        $this->maybe_notify_admin( $subscriber );
        $this->maybe_send_welcome_email( $subscriber );
        $result = array( 'ok' => true, 'step' => 'done', 'message' => $settings['success_message'] );
        return apply_filters( 'ozd_ebulten_subscribe_result', $result, $subscriber );
    }

    /**
     * Sends confirmation email.
     *
     * @param object $subscriber Subscriber row.
     * @return bool
     */
    public function send_confirmation_email( $subscriber ) {
        if ( ! $subscriber || empty( $subscriber->email ) || empty( $subscriber->confirmation_token ) ) {
            return false;
        }

        $settings        = OZD_EBulten_Helpers::settings();
        $confirm_url     = OZD_EBulten_Helpers::action_url( 'confirm', $subscriber->confirmation_token );
        $unsubscribe_url = OZD_EBulten_Helpers::action_url( 'unsubscribe', $subscriber->confirmation_token );
        $subject         = OZD_EBulten_Helpers::replace_tags( $settings['confirmation_subject'], $subscriber, array( '{confirm_url}' => $confirm_url, '{unsubscribe_url}' => $unsubscribe_url ) );
        $body            = OZD_EBulten_Helpers::replace_tags( $settings['confirmation_body'], $subscriber, array( '{confirm_url}' => $confirm_url, '{unsubscribe_url}' => $unsubscribe_url ) );
        $mail            = apply_filters(
            'ozd_ebulten_confirmation_mail',
            array(
                'to'      => $subscriber->email,
                'subject' => $subject,
                'body'    => $body,
                'headers' => OZD_EBulten_Helpers::mail_headers(),
            ),
            $subscriber
        );

        do_action( 'ozd_ebulten_before_send_confirmation_mail', $subscriber, $mail );
        $sent = wp_mail( $mail['to'], $mail['subject'], $mail['body'], $mail['headers'] );
        do_action( 'ozd_ebulten_after_send_confirmation_mail', $subscriber, $sent, $mail );

        return $sent;
    }

    /**
     * Maybe notifies admin.
     *
     * @param object $subscriber Subscriber row.
     */
    private function maybe_notify_admin( $subscriber ) {
        $settings = OZD_EBulten_Helpers::settings();
        if ( ! OZD_EBulten_Helpers::bool( $settings['notify_admin'] ) || ! is_email( $settings['admin_email'] ) ) {
            return;
        }

        $subject = __( 'Yeni e-bülten aboneliği', 'ozd-wp-e-bulten' );
        $body    = sprintf(
            /* translators: 1: email, 2: name, 3: status */
            __( "Yeni bir e-bülten aboneliği kaydedildi.\n\nE-posta: %1\$s\nAd Soyad: %2\$s\nDurum: %3\$s", 'ozd-wp-e-bulten' ),
            $subscriber->email,
            $subscriber->name,
            $subscriber->status
        );
        $mail    = apply_filters(
            'ozd_ebulten_admin_notification_mail',
            array(
                'to'      => $settings['admin_email'],
                'subject' => $subject,
                'body'    => $body,
                'headers' => OZD_EBulten_Helpers::mail_headers(),
            ),
            $subscriber
        );

        wp_mail( $mail['to'], $mail['subject'], $mail['body'], $mail['headers'] );
    }

    /**
     * Maybe sends welcome email.
     *
     * @param object $subscriber Subscriber row.
     * @return bool
     */
    private function maybe_send_welcome_email( $subscriber ) {
        $settings = OZD_EBulten_Helpers::settings();
        if ( ! OZD_EBulten_Helpers::bool( $settings['send_welcome_email'] ) || ! $subscriber || ! is_email( $subscriber->email ) ) {
            return false;
        }

        if ( ! empty( $subscriber->welcome_sent_at ) ) {
            return true;
        }

        $unsubscribe_url = OZD_EBulten_Helpers::action_url( 'unsubscribe', $subscriber->confirmation_token );
        $subject         = OZD_EBulten_Helpers::replace_tags( $settings['welcome_subject'], $subscriber, array( '{unsubscribe_url}' => $unsubscribe_url ) );
        $body            = OZD_EBulten_Helpers::replace_tags( $settings['welcome_body'], $subscriber, array( '{unsubscribe_url}' => $unsubscribe_url ) );
        $mail            = apply_filters(
            'ozd_ebulten_welcome_mail',
            array(
                'to'      => $subscriber->email,
                'subject' => $subject,
                'body'    => $body,
                'headers' => OZD_EBulten_Helpers::mail_headers(),
            ),
            $subscriber
        );

        do_action( 'ozd_ebulten_before_send_welcome_mail', $subscriber, $mail );
        $sent = wp_mail( $mail['to'], $mail['subject'], $mail['body'], $mail['headers'] );
        do_action( 'ozd_ebulten_after_send_welcome_mail', $subscriber, $sent, $mail );

        if ( $sent ) {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Mark welcome email as sent.
            $wpdb->update( $wpdb->prefix . OZD_EBULTEN_TABLE, array( 'welcome_sent_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $subscriber->id ) );
        }

        return $sent;
    }

    /**
     * Confirms subscription.
     *
     * @param string $token Confirmation token.
     */
    private function confirm_subscription( $token ) {
        global $wpdb;

        $table      = $wpdb->prefix . OZD_EBULTEN_TABLE;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Public token lookup.
        $subscriber = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE confirmation_token = %s', $table, $token ) );
        $settings   = OZD_EBulten_Helpers::settings();
        $message    = $settings['invalid_token_message'];
        $type       = 'error';

        if ( $subscriber ) {
            $expired = false;
            if ( ! empty( $subscriber->token_created_at ) ) {
                $expires = strtotime( $subscriber->token_created_at . ' +' . absint( $settings['token_expire_days'] ) . ' days' );
                $expired = $expires && time() > $expires;
            }

            if ( $expired && 'confirmed' !== $subscriber->status ) {
                $message = $settings['invalid_token_message'];
            } elseif ( 'confirmed' === $subscriber->status ) {
                $message = __( 'Aboneliğiniz zaten onaylanmış.', 'ozd-wp-e-bulten' );
                $type    = 'success';
            } else {
                do_action( 'ozd_ebulten_before_confirm_subscriber', $subscriber );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Confirm subscriber in custom table.
                $wpdb->update( $table, array( 'status' => 'confirmed', 'confirmed_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $subscriber->id ) );
                $message                  = $settings['success_message'];
                $type                     = 'success';
                $subscriber->status       = 'confirmed';
                $subscriber->confirmed_at = current_time( 'mysql' );
                $this->maybe_notify_admin( $subscriber );
                $this->maybe_send_welcome_email( $subscriber );
                do_action( 'ozd_ebulten_after_confirm_subscriber', $subscriber );
            }
        }

        $this->redirect_with_message( $message, $type );
    }

    /**
     * Unsubscribes user.
     *
     * @param string $token Confirmation token.
     */
    private function unsubscribe( $token ) {
        global $wpdb;

        $settings = OZD_EBulten_Helpers::settings();
        if ( ! OZD_EBulten_Helpers::bool( $settings['enable_unsubscribe'] ) ) {
            $this->redirect_with_message( __( 'Abonelikten çıkma bağlantısı şu anda aktif değil.', 'ozd-wp-e-bulten' ), 'error' );
        }

        $table      = $wpdb->prefix . OZD_EBULTEN_TABLE;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Public token lookup.
        $subscriber = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE confirmation_token = %s', $table, $token ) );
        $message    = __( 'Geçersiz abonelikten çıkma bağlantısı.', 'ozd-wp-e-bulten' );
        $type       = 'error';

        if ( $subscriber ) {
            do_action( 'ozd_ebulten_before_unsubscribe_subscriber', $subscriber );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Unsubscribe subscriber in custom table.
            $wpdb->update( $table, array( 'status' => 'unsubscribed', 'unsubscribed_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $subscriber->id ) );
            $message = __( 'Abonelikten çıkma işleminiz tamamlandı.', 'ozd-wp-e-bulten' );
            $type    = 'success';
            do_action( 'ozd_ebulten_after_unsubscribe_subscriber', $subscriber );
        }

        $this->redirect_with_message( $message, $type );
    }

    /**
     * Redirects with frontend message.
     *
     * @param string $message Message text.
     * @param string $type Message type.
     */
    private function redirect_with_message( $message, $type ) {
        $url = add_query_arg(
            array(
                'ozd_ebulten_msg'  => rawurlencode( $message ),
                'ozd_ebulten_type' => $type,
            ),
            home_url( '/' )
        );
        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Returns standardized error payload.
     *
     * @param string $message Error message.
     * @return array
     */
    private function fail( $message ) {
        return apply_filters( 'ozd_ebulten_error_result', array( 'ok' => false, 'message' => $message ), $message );
    }
}
