<?php
/**
 * Admin functionality.
 *
 * @package OZD_WP_EBulten
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin pages and actions.
 */
class OZD_EBulten_Admin {
    /** Registers admin hooks. */
    public function hooks() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_ozd_ebulten_delete_subscriber', array( $this, 'delete_subscriber' ) );
        add_action( 'admin_post_ozd_ebulten_change_status', array( $this, 'change_status' ) );
        add_action( 'admin_post_ozd_ebulten_export_csv', array( $this, 'export_csv' ) );
        add_action( 'admin_post_ozd_ebulten_resend_confirmation', array( $this, 'resend_confirmation' ) );
        add_action( 'admin_post_ozd_ebulten_reset_settings', array( $this, 'reset_settings' ) );
        add_action( 'load-toplevel_page_ozd-ebulten', array( $this, 'handle_bulk_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Enqueues admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( false === strpos( (string) $hook, 'ozd-ebulten' ) ) {
            return;
        }

        wp_enqueue_style( 'ozd-ebulten-admin', OZD_EBULTEN_URL . 'assets/css/ozd-bulten-admin.css', array(), OZD_EBULTEN_VERSION );
    }

    /** Adds menu pages. */
    public function menu() {
        add_menu_page( __( 'OZD E-Bülten', 'ozd-wp-e-bulten' ), __( 'OZD E-Bülten', 'ozd-wp-e-bulten' ), 'manage_options', 'ozd-ebulten', array( $this, 'subscribers_page' ), 'dashicons-email-alt2', 58 );
        add_submenu_page( 'ozd-ebulten', __( 'Aboneler', 'ozd-wp-e-bulten' ), __( 'Aboneler', 'ozd-wp-e-bulten' ), 'manage_options', 'ozd-ebulten', array( $this, 'subscribers_page' ) );
        add_submenu_page( 'ozd-ebulten', __( 'Ayarlar', 'ozd-wp-e-bulten' ), __( 'Ayarlar', 'ozd-wp-e-bulten' ), 'manage_options', 'ozd-ebulten-settings', array( $this, 'settings_page' ) );
    }

    /** Registers settings. */
    public function register_settings() {
        register_setting(
            'ozd_ebulten_settings_group',
            OZD_EBULTEN_OPTION,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => OZD_EBulten_Activator::default_settings(),
            )
        );
    }

    /**
     * Sanitizes settings.
     *
     * @param array $input Raw settings input.
     * @return array
     */
    public function sanitize_settings( $input ) {
        $defaults = OZD_EBulten_Activator::default_settings();
        $input    = is_array( $input ) ? $input : array();

        $sanitized = array(
            'form_title'              => sanitize_text_field( $input['form_title'] ?? $defaults['form_title'] ),
            'form_description'        => sanitize_textarea_field( $input['form_description'] ?? $defaults['form_description'] ),
            'show_name_field'         => ! empty( $input['show_name_field'] ) ? '1' : '0',
            'require_name'            => ! empty( $input['require_name'] ) ? '1' : '0',
            'name_label'              => sanitize_text_field( $input['name_label'] ?? $defaults['name_label'] ),
            'email_label'             => sanitize_text_field( $input['email_label'] ?? $defaults['email_label'] ),
            'button_text'             => sanitize_text_field( $input['button_text'] ?? $defaults['button_text'] ),
            'confirm_button_text'     => sanitize_text_field( $input['confirm_button_text'] ?? $defaults['confirm_button_text'] ),
            'success_message'         => sanitize_text_field( $input['success_message'] ?? $defaults['success_message'] ),
            'pending_message'         => sanitize_text_field( $input['pending_message'] ?? $defaults['pending_message'] ),
            'already_message'         => sanitize_text_field( $input['already_message'] ?? $defaults['already_message'] ),
            'pending_already_message' => sanitize_text_field( $input['pending_already_message'] ?? $defaults['pending_already_message'] ),
            'unsubscribed_message'    => sanitize_text_field( $input['unsubscribed_message'] ?? $defaults['unsubscribed_message'] ),
            'invalid_token_message'   => sanitize_text_field( $input['invalid_token_message'] ?? $defaults['invalid_token_message'] ),
            'consent_text'            => sanitize_textarea_field( $input['consent_text'] ?? $defaults['consent_text'] ),
            'consent_version'         => sanitize_text_field( $input['consent_version'] ?? $defaults['consent_version'] ),
            'privacy_url'             => esc_url_raw( $input['privacy_url'] ?? $defaults['privacy_url'] ),
            'terms_url'               => esc_url_raw( $input['terms_url'] ?? $defaults['terms_url'] ),
            'enable_ajax'             => ! empty( $input['enable_ajax'] ) ? '1' : '0',
            'enable_double_optin'     => ! empty( $input['enable_double_optin'] ) ? '1' : '0',
            'enable_unsubscribe'      => ! empty( $input['enable_unsubscribe'] ) ? '1' : '0',
            'sender_name'             => sanitize_text_field( $input['sender_name'] ?? $defaults['sender_name'] ),
            'sender_email'            => sanitize_email( $input['sender_email'] ?? $defaults['sender_email'] ),
            'confirmation_subject'    => sanitize_text_field( $input['confirmation_subject'] ?? $defaults['confirmation_subject'] ),
            'confirmation_body'       => sanitize_textarea_field( $input['confirmation_body'] ?? $defaults['confirmation_body'] ),
            'send_welcome_email'      => ! empty( $input['send_welcome_email'] ) ? '1' : '0',
            'welcome_subject'         => sanitize_text_field( $input['welcome_subject'] ?? $defaults['welcome_subject'] ),
            'welcome_body'            => sanitize_textarea_field( $input['welcome_body'] ?? $defaults['welcome_body'] ),
            'token_expire_days'       => max( 1, min( 30, absint( $input['token_expire_days'] ?? $defaults['token_expire_days'] ) ) ),
            'rate_limit_count'        => max( 1, min( 50, absint( $input['rate_limit_count'] ?? $defaults['rate_limit_count'] ) ) ),
            'rate_limit_minutes'      => max( 5, min( 1440, absint( $input['rate_limit_minutes'] ?? $defaults['rate_limit_minutes'] ) ) ),
            'notify_admin'            => ! empty( $input['notify_admin'] ) ? '1' : '0',
            'admin_email'             => sanitize_email( $input['admin_email'] ?? $defaults['admin_email'] ),
            'cleanup_on_uninstall'    => ! empty( $input['cleanup_on_uninstall'] ) ? '1' : '0',
            'per_page'                => max( 10, min( 100, absint( $input['per_page'] ?? $defaults['per_page'] ) ) ),
        );

        return apply_filters( 'ozd_ebulten_sanitized_settings', $sanitized, $input, $defaults );
    }

    /** Settings page. */
    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = OZD_EBulten_Helpers::settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'OZD E-Bülten Ayarları', 'ozd-wp-e-bulten' ); ?></h1>
            <?php settings_errors(); ?>
            <?php $this->settings_reset_notice(); ?>
            <p><?php esc_html_e( 'Form, onay, e-posta gönderimi ve veri saklama davranışlarını buradan yönetebilirsiniz.', 'ozd-wp-e-bulten' ); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields( 'ozd_ebulten_settings_group' ); ?>
                <?php $this->settings_section_form( $settings ); ?>
                <?php $this->settings_section_messages( $settings ); ?>
                <?php $this->settings_section_email( $settings ); ?>
                <?php $this->settings_section_data( $settings ); ?>
                <?php submit_button( __( 'Ayarları Kaydet', 'ozd-wp-e-bulten' ) ); ?>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Ayarları Sıfırla', 'ozd-wp-e-bulten' ); ?></h2>
            <p><?php esc_html_e( 'Bu işlem yalnızca eklenti ayarlarını varsayılan değerlere döndürür. Abone kayıtları silinmez.', 'ozd-wp-e-bulten' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'ozd_ebulten_reset_settings' ); ?>
                <input type="hidden" name="action" value="ozd_ebulten_reset_settings">
                <p class="submit">
                    <button
                        type="submit"
                        class="button button-secondary"
                        onclick="return confirm('<?php echo esc_js( __( 'Tüm eklenti ayarları varsayılan değerlere döndürülecek. Devam etmek istiyor musunuz?', 'ozd-wp-e-bulten' ) ); ?>');"
                    >
                        <?php esc_html_e( 'Ayarları Varsayılana Döndür', 'ozd-wp-e-bulten' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Displays the settings reset notice.
     */
    private function settings_reset_notice() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag after a verified redirect.
        if ( empty( $_GET['ozd_settings_reset'] ) ) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Eklenti ayarları varsayılan değerlere döndürüldü.', 'ozd-wp-e-bulten' ) . '</p></div>';
    }

    /**
     * Resets plugin settings to their defaults.
     */
    public function reset_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Bu işlem için yetkiniz yok.', 'ozd-wp-e-bulten' ) );
        }

        check_admin_referer( 'ozd_ebulten_reset_settings' );

        update_option( OZD_EBULTEN_OPTION, OZD_EBulten_Activator::default_settings() );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'               => 'ozd-ebulten-settings',
                    'ozd_settings_reset' => '1',
                ),
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Renders form settings.
     *
     * @param array $settings Current settings.
     */
    private function settings_section_form( $settings ) {
        ?>
        <h2><?php esc_html_e( 'Form Ayarları', 'ozd-wp-e-bulten' ); ?></h2>
        <table class="form-table" role="presentation">
            <?php $this->text_row( 'form_title', __( 'Form başlığı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->textarea_row( 'form_description', __( 'Form açıklaması', 'ozd-wp-e-bulten' ), $settings, 2 ); ?>
            <tr><th scope="row"><?php esc_html_e( 'Ad soyad alanı', 'ozd-wp-e-bulten' ); ?></th><td><?php $this->checkbox( 'show_name_field', __( 'Ad soyad alanını göster', 'ozd-wp-e-bulten' ), $settings ); ?><br><?php $this->checkbox( 'require_name', __( 'Ad soyad alanı zorunlu olsun', 'ozd-wp-e-bulten' ), $settings ); ?></td></tr>
            <?php $this->text_row( 'name_label', __( 'Ad soyad etiketi', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'email_label', __( 'E-posta etiketi', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'button_text', __( 'İlk buton metni', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'confirm_button_text', __( 'Onay butonu metni', 'ozd-wp-e-bulten' ), $settings ); ?>
            <tr><th scope="row"><?php esc_html_e( 'AJAX', 'ozd-wp-e-bulten' ); ?></th><td><?php $this->checkbox( 'enable_ajax', __( 'Form AJAX ile gönderilsin', 'ozd-wp-e-bulten' ), $settings ); ?></td></tr>
        </table>
        <?php
    }

    /**
     * Renders message settings.
     *
     * @param array $settings Current settings.
     */
    private function settings_section_messages( $settings ) {
        ?>
        <h2><?php esc_html_e( 'Mesaj ve Onay Ayarları', 'ozd-wp-e-bulten' ); ?></h2>
        <table class="form-table" role="presentation">
            <?php $this->text_row( 'success_message', __( 'Başarı mesajı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'pending_message', __( 'E-posta onayı bekliyor mesajı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'already_message', __( 'Zaten kayıtlı mesajı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'pending_already_message', __( 'Onay bekleyen kayıt mesajı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'unsubscribed_message', __( 'Abonelikten çıkmış kayıt mesajı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'invalid_token_message', __( 'Geçersiz bağlantı mesajı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->textarea_row( 'consent_text', __( 'Onay metni', 'ozd-wp-e-bulten' ), $settings, 3 ); ?>
            <?php $this->text_row( 'consent_version', __( 'Onay metni versiyonu', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->url_row( 'privacy_url', __( 'Gizlilik politikası URL', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->url_row( 'terms_url', __( 'Kullanım koşulları URL', 'ozd-wp-e-bulten' ), $settings ); ?>
        </table>
        <?php
    }

    /**
     * Renders email settings.
     *
     * @param array $settings Current settings.
     */
    private function settings_section_email( $settings ) {
        ?>
        <h2><?php esc_html_e( 'E-posta Ayarları', 'ozd-wp-e-bulten' ); ?></h2>
        <table class="form-table" role="presentation">
            <tr><th scope="row"><?php esc_html_e( 'Çift onay', 'ozd-wp-e-bulten' ); ?></th><td><?php $this->checkbox( 'enable_double_optin', __( 'E-posta bağlantısı ile onay zorunlu olsun', 'ozd-wp-e-bulten' ), $settings ); ?></td></tr>
            <tr><th scope="row"><?php esc_html_e( 'Abonelikten çıkma', 'ozd-wp-e-bulten' ); ?></th><td><?php $this->checkbox( 'enable_unsubscribe', __( 'Abonelikten çıkma bağlantısı üretimi desteklensin', 'ozd-wp-e-bulten' ), $settings ); ?></td></tr>
            <?php $this->text_row( 'sender_name', __( 'Gönderen adı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->email_row( 'sender_email', __( 'Gönderen e-posta', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->text_row( 'confirmation_subject', __( 'Onay e-postası konu başlığı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->textarea_row( 'confirmation_body', __( 'Onay e-postası metni', 'ozd-wp-e-bulten' ), $settings, 8 ); ?>
            <tr><th scope="row"><?php esc_html_e( 'Kullanılabilir etiketler', 'ozd-wp-e-bulten' ); ?></th><td><code>{site_name}</code> <code>{name}</code> <code>{email}</code> <code>{confirm_url}</code> <code>{unsubscribe_url}</code></td></tr>
            <tr><th scope="row"><?php esc_html_e( 'Hoş geldin e-postası', 'ozd-wp-e-bulten' ); ?></th><td><?php $this->checkbox( 'send_welcome_email', __( 'Abonelik onaylanınca bilgilendirme e-postası gönder', 'ozd-wp-e-bulten' ), $settings ); ?></td></tr>
            <?php $this->text_row( 'welcome_subject', __( 'Hoş geldin e-postası konu başlığı', 'ozd-wp-e-bulten' ), $settings ); ?>
            <?php $this->textarea_row( 'welcome_body', __( 'Hoş geldin e-postası metni', 'ozd-wp-e-bulten' ), $settings, 6 ); ?>
            <tr><th scope="row"><?php esc_html_e( 'Yönetici bildirimi', 'ozd-wp-e-bulten' ); ?></th><td><?php $this->checkbox( 'notify_admin', __( 'Yeni onaylanmış abonelikte yöneticiye e-posta gönder', 'ozd-wp-e-bulten' ), $settings ); ?></td></tr>
            <?php $this->email_row( 'admin_email', __( 'Yönetici bildirim e-postası', 'ozd-wp-e-bulten' ), $settings ); ?>
        </table>
        <?php
    }

    /**
     * Renders data settings.
     *
     * @param array $settings Current settings.
     */
    private function settings_section_data( $settings ) {
        ?>
        <h2><?php esc_html_e( 'Veri ve Yönetim Ayarları', 'ozd-wp-e-bulten' ); ?></h2>
        <table class="form-table" role="presentation">
            <?php $this->number_row( 'per_page', __( 'Sayfa başına abone', 'ozd-wp-e-bulten' ), $settings, 10, 100 ); ?>
            <?php $this->number_row( 'token_expire_days', __( 'Onay bağlantısı geçerlilik süresi/gün', 'ozd-wp-e-bulten' ), $settings, 1, 30 ); ?>
            <?php $this->number_row( 'rate_limit_count', __( 'Form deneme limiti', 'ozd-wp-e-bulten' ), $settings, 1, 50 ); ?>
            <?php $this->number_row( 'rate_limit_minutes', __( 'Deneme limiti süresi/dakika', 'ozd-wp-e-bulten' ), $settings, 5, 1440 ); ?>
            <tr><th scope="row"><?php esc_html_e( 'Kaldırma temizliği', 'ozd-wp-e-bulten' ); ?></th><td><?php $this->checkbox( 'cleanup_on_uninstall', __( 'Eklenti silinirken tablo ve ayarları kaldır', 'ozd-wp-e-bulten' ), $settings ); ?><p class="description"><?php esc_html_e( 'Bu seçenek açıksa eklenti WordPress üzerinden silindiğinde abonelik tablosu ve ayarlar kaldırılır.', 'ozd-wp-e-bulten' ); ?></p></td></tr>
        </table>
        <?php
    }

    /**
     * Renders a checkbox field.
     *
     * @param string $key Settings key.
     * @param string $label Field label.
     * @param array  $settings Current settings.
     */
    private function checkbox( $key, $label, $settings ) {
        printf( '<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>', esc_attr( OZD_EBULTEN_OPTION ), esc_attr( $key ), checked( $settings[ $key ] ?? '0', '1', false ), esc_html( $label ) );
    }
    /**
     * Renders a text input row.
     *
     * @param string $key Settings key.
     * @param string $label Field label.
     * @param array  $settings Current settings.
     */
    private function text_row( $key, $label, $settings ) {
        printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input id="%1$s" type="text" class="regular-text" name="%3$s[%1$s]" value="%4$s"></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( OZD_EBULTEN_OPTION ), esc_attr( $settings[ $key ] ?? '' ) );
    }
    /**
     * Renders an email input row.
     *
     * @param string $key Settings key.
     * @param string $label Field label.
     * @param array  $settings Current settings.
     */
    private function email_row( $key, $label, $settings ) {
        printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input id="%1$s" type="email" class="regular-text" name="%3$s[%1$s]" value="%4$s"></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( OZD_EBULTEN_OPTION ), esc_attr( $settings[ $key ] ?? '' ) );
    }
    /**
     * Renders a number input row.
     *
     * @param string $key Settings key.
     * @param string $label Field label.
     * @param array  $settings Current settings.
     * @param int    $min Minimum value.
     * @param int    $max Maximum value.
     */
    private function number_row( $key, $label, $settings, $min, $max ) {
        printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input id="%1$s" type="number" min="%5$d" max="%6$d" class="small-text" name="%3$s[%1$s]" value="%4$s"></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( OZD_EBULTEN_OPTION ), esc_attr( $settings[ $key ] ?? '' ), (int) $min, (int) $max );
    }
    /**
     * Renders a textarea row.
     *
     * @param string $key Settings key.
     * @param string $label Field label.
     * @param array  $settings Current settings.
     * @param int    $rows Textarea rows.
     */
    private function textarea_row( $key, $label, $settings, $rows = 3 ) {
        printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%3$s[%1$s]" class="large-text" rows="%5$d">%4$s</textarea></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( OZD_EBULTEN_OPTION ), esc_textarea( $settings[ $key ] ?? '' ), (int) $rows );
    }
    /**
     * Renders a URL input row.
     *
     * @param string $key Settings key.
     * @param string $label Field label.
     * @param array  $settings Current settings.
     */
    private function url_row( $key, $label, $settings ) {
        printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input id="%1$s" type="url" class="regular-text" name="%3$s[%1$s]" value="%4$s"></td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( OZD_EBULTEN_OPTION ), esc_url( $settings[ $key ] ?? '' ) );
    }

    /** Subscribers page. */
    public function subscribers_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $table = new OZD_EBulten_Subscribers_Table();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'OZD E-Bülten Aboneleri', 'ozd-wp-e-bulten' ); ?></h1>
            <p><?php esc_html_e( 'Kısa kod:', 'ozd-wp-e-bulten' ); ?> <code>[ozd_e_bulten]</code></p>
            <?php $this->stats_boxes(); ?>
            <?php $this->admin_notice(); ?>
            <form method="get">
                <input type="hidden" name="page" value="ozd-ebulten">
                <?php $table->search_box( __( 'Abone ara', 'ozd-wp-e-bulten' ), 'ozd-ebulten-search' ); ?>
                <?php $table->display(); ?>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ozd-admin-export">
                <?php wp_nonce_field( 'ozd_ebulten_export_csv' ); ?>
                <input type="hidden" name="action" value="ozd_ebulten_export_csv">
                <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only to keep the current list filter in export form. ?>
                <input type="hidden" name="status" value="<?php echo esc_attr( isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '' ); ?>">
                <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only to keep the current list search in export form. ?>
                <input type="hidden" name="s" value="<?php echo esc_attr( isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '' ); ?>">
                <?php submit_button( __( 'CSV Dışa Aktar', 'ozd-wp-e-bulten' ), 'secondary', '', false ); ?>
            </form>
        </div>
        <?php
    }

    /** Admin notice. */
    private function admin_notice() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice message.
        if ( empty( $_GET['ozd_notice'] ) ) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice message.
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['ozd_notice'] ) ) ) . '</p></div>';
    }

    /** Stats boxes. */
    private function stats_boxes() {
        global $wpdb;
        $table  = $wpdb->prefix . OZD_EBULTEN_TABLE;
        $counts = array( 'confirmed' => 0, 'pending' => 0, 'unsubscribed' => 0 );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table statistics.
        $rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) as total FROM %i GROUP BY status', $table ) );

        foreach ( (array) $rows as $row ) {
            if ( isset( $counts[ $row->status ] ) ) {
                $counts[ $row->status ] = (int) $row->total;
            }
        }

        echo '<div class="ozd-admin-stats">';
        foreach ( $counts as $label => $count ) {
            $statuses = OZD_EBulten_Helpers::allowed_statuses();
            $title    = isset( $statuses[ $label ] ) ? $statuses[ $label ] : $label;
            echo '<div class="ozd-admin-stat-card"><strong>' . esc_html( $title ) . '</strong><br><span>' . (int) $count . '</span></div>';
        }
        echo '</div>';
    }

    /** Handles bulk actions. */
    public function handle_bulk_actions() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified after a valid bulk action is detected.
        if ( ! current_user_can( 'manage_options' ) || empty( $_REQUEST['action'] ) || '-1' === $_REQUEST['action'] ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified after a valid bulk action is detected.
        $action = sanitize_key( wp_unslash( $_REQUEST['action'] ) );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified after a valid bulk action is detected.
        if ( ! in_array( $action, array( 'confirmed', 'unsubscribed', 'delete' ), true ) || empty( $_REQUEST['subscriber'] ) || ! is_array( $_REQUEST['subscriber'] ) ) {
            return;
        }

        check_admin_referer( 'bulk-subscribers' );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by array_map( 'absint' ).
        $ids = array_map( 'absint', wp_unslash( $_REQUEST['subscriber'] ) );
        $ids = array_filter( $ids );

        if ( $ids ) {
            global $wpdb;

            $table = $wpdb->prefix . OZD_EBULTEN_TABLE;
            $now   = current_time( 'mysql' );

            foreach ( $ids as $id ) {
                if ( 'delete' === $action ) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table write.
                    $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
                } else {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table write.
                    $wpdb->update(
                        $table,
                        array(
                            'status'     => $action,
                            'updated_at' => $now,
                        ),
                        array( 'id' => $id ),
                        array( '%s', '%s' ),
                        array( '%d' )
                    );
                }
            }
        }

        wp_safe_redirect( add_query_arg( 'ozd_notice', rawurlencode( __( 'Toplu işlem tamamlandı.', 'ozd-wp-e-bulten' ) ), admin_url( 'admin.php?page=ozd-ebulten' ) ) );
        exit;
    }

    /** Deletes subscriber. */
    public function delete_subscriber() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkisiz işlem.', 'ozd-wp-e-bulten' ) );
        }

        $id = isset( $_REQUEST['subscriber_id'] ) ? absint( $_REQUEST['subscriber_id'] ) : 0;
        check_admin_referer( 'ozd_ebulten_delete_' . $id );

        if ( $id ) {
            global $wpdb;
            do_action( 'ozd_ebulten_before_admin_delete_subscriber', $id );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table write.
            $wpdb->delete( $wpdb->prefix . OZD_EBULTEN_TABLE, array( 'id' => $id ) );
            do_action( 'ozd_ebulten_after_admin_delete_subscriber', $id );
        }

        wp_safe_redirect( add_query_arg( 'ozd_notice', rawurlencode( __( 'Abone silindi.', 'ozd-wp-e-bulten' ) ), admin_url( 'admin.php?page=ozd-ebulten' ) ) );
        exit;
    }

    /** Changes subscriber status. */
    public function change_status() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkisiz işlem.', 'ozd-wp-e-bulten' ) );
        }

        $id     = isset( $_REQUEST['subscriber_id'] ) ? absint( $_REQUEST['subscriber_id'] ) : 0;
        $status = isset( $_REQUEST['new_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['new_status'] ) ) : '';
        check_admin_referer( 'ozd_ebulten_status_' . $id );

        $statuses = OZD_EBulten_Helpers::allowed_statuses();
        if ( $id && array_key_exists( $status, $statuses ) ) {
            global $wpdb;
            do_action( 'ozd_ebulten_before_admin_change_status', $id, $status );
            $data = array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) );
            if ( 'confirmed' === $status ) {
                $data['confirmed_at'] = current_time( 'mysql' );
            }
            if ( 'unsubscribed' === $status ) {
                $data['unsubscribed_at'] = current_time( 'mysql' );
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table write.
            $wpdb->update( $wpdb->prefix . OZD_EBULTEN_TABLE, $data, array( 'id' => $id ) );
            do_action( 'ozd_ebulten_after_admin_change_status', $id, $status, $data );
        }

        wp_safe_redirect( add_query_arg( 'ozd_notice', rawurlencode( __( 'Abone durumu güncellendi.', 'ozd-wp-e-bulten' ) ), admin_url( 'admin.php?page=ozd-ebulten' ) ) );
        exit;
    }

    /** Exports CSV. */
    public function export_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkisiz işlem.', 'ozd-wp-e-bulten' ) );
        }
        check_admin_referer( 'ozd_ebulten_export_csv' );

        global $wpdb;
        $table    = $wpdb->prefix . OZD_EBULTEN_TABLE;
        $search   = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
        $status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
        $statuses = OZD_EBulten_Helpers::allowed_statuses();
        $where    = 'WHERE 1=1';
        $params   = array();

        if ( '' !== $search ) {
            $where   .= ' AND (email LIKE %s OR name LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ( array_key_exists( $status, $statuses ) ) {
            $where   .= ' AND status = %s';
            $params[] = $status;
        }

        $sql = "SELECT name,email,status,ip_address,source_url,consent_text_version,confirmed_at,unsubscribed_at,created_at FROM {$table} {$where} ORDER BY id DESC";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query is built with fixed fragments and prepared when parameters exist.
        $rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=ozd-ebulten-aboneler-' . gmdate( 'Y-m-d' ) . '.csv' );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming CSV download output.
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'name', 'email', 'status', 'ip_address', 'source_url', 'consent_text_version', 'confirmed_at', 'unsubscribed_at', 'created_at' ) );
        foreach ( $rows as $row ) {
            fputcsv( $out, $row );
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming CSV download output.
        fclose( $out );
        exit;
    }

    /** Resends confirmation email. */
    public function resend_confirmation() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkisiz işlem.', 'ozd-wp-e-bulten' ) );
        }

        $id = isset( $_REQUEST['subscriber_id'] ) ? absint( $_REQUEST['subscriber_id'] ) : 0;
        check_admin_referer( 'ozd_ebulten_resend_' . $id );

        global $wpdb;
        $table      = $wpdb->prefix . OZD_EBULTEN_TABLE;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fetching one subscriber for resend action.
        $subscriber = $id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id ) ) : null;
        $notice     = __( 'Abone bulunamadı.', 'ozd-wp-e-bulten' );

        if ( $subscriber && 'pending' === $subscriber->status ) {
            if ( empty( $subscriber->confirmation_token ) ) {
                $token = OZD_EBulten_Helpers::token();
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom subscriber table write.
            $wpdb->update( $table, array( 'confirmation_token' => $token, 'token_created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
                $subscriber->confirmation_token = $token;
            }
            $public = new OZD_EBulten_Public();
            $notice = $public->send_confirmation_email( $subscriber ) ? __( 'Onay e-postası tekrar gönderildi.', 'ozd-wp-e-bulten' ) : __( 'Onay e-postası gönderilemedi.', 'ozd-wp-e-bulten' );
        }

        wp_safe_redirect( add_query_arg( 'ozd_notice', rawurlencode( $notice ), admin_url( 'admin.php?page=ozd-ebulten' ) ) );
        exit;
    }
}
