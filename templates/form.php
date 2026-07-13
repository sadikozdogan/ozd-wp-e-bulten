<?php
/**
 * Frontend newsletter form template.
 *
 * Override by copying this file to your theme:
 * your-theme/ozd-wp-e-bulten/form.php
 *
 * @package OZD_WP_EBulten
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ozd-bulten" data-ozd-ebulten>
    <?php if ( ! empty( $settings['form_title'] ) ) : ?>
        <h3 class="ozd-bulten-title"><?php echo esc_html( $settings['form_title'] ); ?></h3>
    <?php endif; ?>

    <?php if ( ! empty( $settings['form_description'] ) ) : ?>
        <p class="ozd-bulten-desc"><?php echo esc_html( $settings['form_description'] ); ?></p>
    <?php endif; ?>

    <div class="ozd-alert <?php echo esc_attr( $query_type ); ?>" data-ozd-message <?php echo '' === $query_message ? 'hidden' : ''; ?>><?php echo esc_html( $query_message ); ?></div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ozd-form" data-ozd-form>
        <?php wp_nonce_field( OZD_EBulten_Public::NONCE_ACTION, 'ozd_ebulten_nonce' ); ?>
        <input type="hidden" name="action" value="ozd_ebulten_subscribe">
        <input type="hidden" name="ozd_step" value="email" data-ozd-step>
        <input type="hidden" name="ozd_source_url" value="<?php echo esc_attr( OZD_EBulten_Helpers::current_url() ); ?>">

        <div class="ozd-hp" aria-hidden="true">
            <label for="ozd_website_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Web siteniz', 'ozd-wp-e-bulten' ); ?></label>
            <input id="ozd_website_<?php echo esc_attr( $uid ); ?>" type="text" name="ozd_website" tabindex="-1" autocomplete="off">
        </div>

        <?php if ( OZD_EBulten_Helpers::bool( $settings['show_name_field'] ) ) : ?>
            <div class="ozd-row" data-ozd-name-row>
                <label for="ozd_name_<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( $settings['name_label'] ); ?></label>
                <input id="ozd_name_<?php echo esc_attr( $uid ); ?>" type="text" name="ozd_name" <?php echo OZD_EBulten_Helpers::bool( $settings['require_name'] ) ? 'required' : ''; ?> autocomplete="name">
            </div>
        <?php endif; ?>

        <div class="ozd-row" data-ozd-email-row>
            <label for="ozd_email_<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( $settings['email_label'] ); ?></label>
            <input id="ozd_email_<?php echo esc_attr( $uid ); ?>" type="email" name="ozd_email" required placeholder="<?php echo esc_attr_x( 'eposta@ornek.com', 'email placeholder', 'ozd-wp-e-bulten' ); ?>" autocomplete="email">
        </div>

        <div class="ozd-row ozd-consent-row" data-ozd-consent-row hidden>
            <div class="ozd-onay-metin">
                <a href="<?php echo esc_url( $settings['privacy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Gizlilik Politikası', 'ozd-wp-e-bulten' ); ?></a>
                <?php esc_html_e( 've', 'ozd-wp-e-bulten' ); ?>
                <a href="<?php echo esc_url( $settings['terms_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Kullanım Koşulları', 'ozd-wp-e-bulten' ); ?></a>
                <?php esc_html_e( 'bağlantılarını inceleyin.', 'ozd-wp-e-bulten' ); ?>
            </div>
            <label class="ozd-check">
                <input type="checkbox" name="ozd_consent" value="1">
                <span><?php echo esc_html( $settings['consent_text'] ); ?></span>
            </label>
        </div>

        <div class="ozd-actions">
            <button type="submit" class="ozd-btn" data-ozd-submit><?php echo esc_html( $settings['button_text'] ); ?></button>
        </div>
    </form>
</div>
