<?php
/**
 * Activation and default settings.
 *
 * @package OZD_WP_EBulten
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation and database installation.
 */
class OZD_EBulten_Activator {
    /**
     * Runs on activation.
     */
    public static function activate() {
        self::create_or_update_table();

        $saved    = get_option( OZD_EBULTEN_OPTION, array() );
        $settings = wp_parse_args( is_array( $saved ) ? $saved : array(), self::default_settings() );

        update_option( OZD_EBULTEN_OPTION, $settings );
        update_option( OZD_EBULTEN_DB_VERSION_OPTION, OZD_EBULTEN_VERSION );
    }

    /**
     * Returns default settings.
     *
     * @return array
     */
    public static function default_settings() {
        $defaults = array(
            'form_title'              => __( 'E-Bülten Aboneliği', 'ozd-wp-e-bulten' ),
            'form_description'        => __( 'Yeni içeriklerden ve duyurulardan haberdar olmak için e-posta listemize katılın.', 'ozd-wp-e-bulten' ),
            'show_name_field'         => '0',
            'require_name'            => '0',
            'name_label'              => __( 'Ad Soyad', 'ozd-wp-e-bulten' ),
            'email_label'             => __( 'E-posta', 'ozd-wp-e-bulten' ),
            'button_text'             => __( 'Abone Ol', 'ozd-wp-e-bulten' ),
            'confirm_button_text'     => __( 'Onayla', 'ozd-wp-e-bulten' ),
            'success_message'         => __( 'Aboneliğiniz başarıyla kaydedildi. Teşekkür ederiz.', 'ozd-wp-e-bulten' ),
            'pending_message'         => __( 'Onay bağlantısı e-posta adresinize gönderildi. Aboneliği tamamlamak için e-postanızı kontrol edin.', 'ozd-wp-e-bulten' ),
            'already_message'         => __( 'Bu e-posta adresi zaten kayıtlı.', 'ozd-wp-e-bulten' ),
            'pending_already_message' => __( 'Bu e-posta adresi için onay bekleyen bir kayıt var. Lütfen e-postanızı kontrol edin.', 'ozd-wp-e-bulten' ),
            'unsubscribed_message'    => __( 'Bu e-posta adresi daha önce abonelikten çıkmış. Yeniden abone olmak için formu tekrar onaylayabilirsiniz.', 'ozd-wp-e-bulten' ),
            'invalid_token_message'   => __( 'Bağlantı geçersiz veya süresi dolmuş.', 'ozd-wp-e-bulten' ),
            'consent_text'            => __( 'Gizlilik Politikası ve Kullanım Koşulları metinlerini okudum, kabul ediyorum.', 'ozd-wp-e-bulten' ),
            'consent_version'         => '1.0',
            'privacy_url'             => home_url( '/gizlilik-politikasi/' ),
            'terms_url'               => home_url( '/kullanim-kosullari/' ),
            'enable_ajax'             => '1',
            'enable_double_optin'     => '1',
            'enable_unsubscribe'      => '1',
            'sender_name'             => get_bloginfo( 'name' ),
            'sender_email'            => get_option( 'admin_email' ),
            'confirmation_subject'    => __( 'E-bülten aboneliğinizi onaylayın', 'ozd-wp-e-bulten' ),
            'confirmation_body'       => __( "Merhaba {name},

E-bülten aboneliğinizi tamamlamak için aşağıdaki bağlantıya tıklayın:

{confirm_url}

Bu isteği siz başlatmadıysanız bu e-postayı yok sayabilirsiniz.", 'ozd-wp-e-bulten' ),
            'send_welcome_email'      => '0',
            'welcome_subject'         => __( 'E-bülten aboneliğiniz tamamlandı', 'ozd-wp-e-bulten' ),
            'welcome_body'            => __( "Merhaba {name},

E-bülten aboneliğiniz başarıyla tamamlandı.

Abonelikten çıkmak isterseniz bu bağlantıyı kullanabilirsiniz:
{unsubscribe_url}", 'ozd-wp-e-bulten' ),
            'token_expire_days'       => '7',
            'rate_limit_count'        => '5',
            'rate_limit_minutes'      => '60',
            'notify_admin'            => '0',
            'admin_email'             => get_option( 'admin_email' ),
            'cleanup_on_uninstall'    => '0',
            'per_page'                => '20',
        );

        return apply_filters( 'ozd_ebulten_default_settings', $defaults );
    }

    /**
     * Creates or updates the subscriber table.
     */
    public static function create_or_update_table() {
        global $wpdb;

        $table   = $wpdb->prefix . OZD_EBULTEN_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) DEFAULT NULL,
            email VARCHAR(191) NOT NULL,
            ip_address VARCHAR(64) DEFAULT NULL,
            user_agent TEXT NULL,
            source_url TEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            consent TINYINT(1) NOT NULL DEFAULT 0,
            consent_text_version VARCHAR(50) DEFAULT NULL,
            confirmation_token VARCHAR(191) DEFAULT NULL,
            token_created_at DATETIME DEFAULT NULL,
            confirmed_at DATETIME DEFAULT NULL,
            unsubscribed_at DATETIME DEFAULT NULL,
            last_error TEXT NULL,
            welcome_sent_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            KEY status (status),
            KEY confirmation_token (confirmation_token),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}
