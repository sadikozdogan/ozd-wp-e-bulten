<?php
/**
 * Subscribers list table.
 *
 * @package OZD_WP_EBulten
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WP_List_Table implementation for subscribers.
 */
class OZD_EBulten_Subscribers_Table extends WP_List_Table {
    /**
     * Current status filter.
     *
     * @var string
     */
    private $status = '';

    /** Constructor. */
    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'subscriber',
                'plural'   => 'subscribers',
                'ajax'     => false,
            )
        );
    }

    /**
     * Returns columns.
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'cb'                   => '<input type="checkbox" />',
            'email'                => __( 'E-posta', 'ozd-wp-e-bulten' ),
            'name'                 => __( 'Ad Soyad', 'ozd-wp-e-bulten' ),
            'status'               => __( 'Durum', 'ozd-wp-e-bulten' ),
            'ip_address'           => __( 'IP', 'ozd-wp-e-bulten' ),
            'source_url'           => __( 'Kaynak', 'ozd-wp-e-bulten' ),
            'consent_text_version' => __( 'Onay Versiyonu', 'ozd-wp-e-bulten' ),
            'created_at'           => __( 'Tarih', 'ozd-wp-e-bulten' ),
        );
    }

    /**
     * Checkbox column.
     *
     * @param object $item Row.
     * @return string
     */
    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="subscriber[]" value="%d" />', (int) $item->id );
    }

    /**
     * Email column with actions.
     *
     * @param object $item Row.
     * @return string
     */
    public function column_email( $item ) {
        $actions = array();
        $actions['confirm'] = $this->action_link( $item->id, 'ozd_ebulten_change_status', 'ozd_ebulten_status_', __( 'Onayla', 'ozd-wp-e-bulten' ), array( 'new_status' => 'confirmed' ) );
        $actions['unsubscribe'] = $this->action_link( $item->id, 'ozd_ebulten_change_status', 'ozd_ebulten_status_', __( 'Çıkar', 'ozd-wp-e-bulten' ), array( 'new_status' => 'unsubscribed' ) );

        if ( 'pending' === $item->status ) {
            $actions['resend'] = $this->action_link( $item->id, 'ozd_ebulten_resend_confirmation', 'ozd_ebulten_resend_', __( 'Onay e-postası gönder', 'ozd-wp-e-bulten' ) );
        }

        $actions['delete'] = '<span class="trash">' . $this->action_link( $item->id, 'ozd_ebulten_delete_subscriber', 'ozd_ebulten_delete_', __( 'Sil', 'ozd-wp-e-bulten' ), array(), 'delete' ) . '</span>';

        $email = '<strong>' . esc_html( $item->email ) . '</strong>';
        if ( ! empty( $item->last_error ) ) {
            $email .= '<br><small class="ozd-admin-error-text">' . esc_html( wp_trim_words( $item->last_error, 8 ) ) . '</small>';
        }

        return $email . $this->row_actions( $actions );
    }

    /**
     * Default column.
     *
     * @param object $item        Row.
     * @param string $column_name Column name.
     * @return string
     */
    public function column_default( $item, $column_name ) {
        $statuses = OZD_EBulten_Helpers::allowed_statuses();

        switch ( $column_name ) {
            case 'status':
                return esc_html( $statuses[ $item->status ] ?? $item->status );
            case 'source_url':
                return esc_html( wp_trim_words( $item->source_url, 8 ) );
            case 'created_at':
                return sprintf(
                    '%1$s<br><small>%2$s: %3$s<br>%4$s: %5$s</small>',
                    esc_html( $item->created_at ),
                    esc_html__( 'Onay', 'ozd-wp-e-bulten' ),
                    esc_html( $item->confirmed_at ),
                    esc_html__( 'Çıkış', 'ozd-wp-e-bulten' ),
                    esc_html( $item->unsubscribed_at )
                );
            default:
                return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
        }
    }

    /**
     * Bulk actions.
     *
     * @return array
     */
    public function get_bulk_actions() {
        return array(
            'confirmed'    => __( 'Onaylandı yap', 'ozd-wp-e-bulten' ),
            'unsubscribed' => __( 'Abonelikten çıkar', 'ozd-wp-e-bulten' ),
            'delete'       => __( 'Sil', 'ozd-wp-e-bulten' ),
        );
    }

    /**
     * Prepares table items.
     */
    public function prepare_items() {
        global $wpdb;

        $settings = OZD_EBulten_Helpers::settings();
        $per_page = max( 10, absint( $settings['per_page'] ?? 20 ) );
        $paged    = max( 1, $this->get_pagenum() );
        $offset   = ( $paged - 1 ) * $per_page;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table search/filter values.
        $search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table search/filter values.
        $status   = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : '';
        $statuses = OZD_EBulten_Helpers::allowed_statuses();
        $table    = $wpdb->prefix . OZD_EBULTEN_TABLE;
        $where    = 'WHERE 1=1';
        $params   = array();

        $this->status = $status;

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

        $count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query uses fixed fragments and prepared search/status parameters.
        $total = $params ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : (int) $wpdb->get_var( $count_sql );
        $sql   = "SELECT id, name, email, status, ip_address, source_url, consent_text_version, confirmed_at, unsubscribed_at, created_at, last_error FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $items     = array_merge( $params, array( $per_page, $offset ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query uses fixed fragments and prepared parameters.
        $this->items = $wpdb->get_results( $wpdb->prepare( $sql, $items ) );

        $this->_column_headers = array( $this->get_columns(), array(), array() );
        $this->set_pagination_args(
            array(
                'total_items' => $total,
                'per_page'    => $per_page,
                'total_pages' => (int) ceil( $total / $per_page ),
            )
        );
    }

    /**
     * Extra controls.
     *
     * @param string $which Table nav position.
     */
    protected function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }

        $statuses = OZD_EBulten_Helpers::allowed_statuses();
        ?>
        <div class="alignleft actions">
            <label class="screen-reader-text" for="ozd-ebulten-status-filter"><?php esc_html_e( 'Duruma göre filtrele', 'ozd-wp-e-bulten' ); ?></label>
            <select name="status" id="ozd-ebulten-status-filter">
                <option value=""><?php esc_html_e( 'Tüm durumlar', 'ozd-wp-e-bulten' ); ?></option>
                <?php foreach ( $statuses as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $this->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button( __( 'Filtrele', 'ozd-wp-e-bulten' ), 'secondary', 'filter_action', false ); ?>
        </div>
        <?php
    }

    /**
     * No items text.
     */
    public function no_items() {
        esc_html_e( 'Henüz abone yok.', 'ozd-wp-e-bulten' );
    }

    /**
     * Builds action link.
     *
     * @param int    $id          Subscriber id.
     * @param string $action      Admin post action.
     * @param string $nonce_base  Nonce base.
     * @param string $label       Link label.
     * @param array  $extra_args  Extra args.
     * @param string $link_class  Link class.
     * @return string
     */
    private function action_link( $id, $action, $nonce_base, $label, $extra_args = array(), $link_class = '' ) {
        $args = array_merge(
            array(
                'action'        => $action,
                'subscriber_id' => (int) $id,
                '_wpnonce'      => wp_create_nonce( $nonce_base . (int) $id ),
            ),
            $extra_args
        );
        $url  = add_query_arg( $args, admin_url( 'admin-post.php' ) );

        return sprintf( '<a class="%1$s" href="%2$s">%3$s</a>', esc_attr( $link_class ), esc_url( $url ), esc_html( $label ) );
    }
}
