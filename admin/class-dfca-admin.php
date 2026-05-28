<?php
/**
 * Admin: menu, page routing, asset loading and POST handlers.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_Admin {

    private static $instance = null;
    public static function instance() {
        return self::$instance ?: self::$instance = new self;
    }
    private function __construct() {
        add_action( 'admin_menu',            [ $this, 'menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        add_action( 'admin_init',            [ $this, 'handle_post' ] );
        add_filter( 'plugin_action_links_' . DFCA_BASENAME, [ $this, 'action_links' ] );
    }

    public function menu() {
        $cap  = 'manage_woocommerce';
        $slug = 'dfca-dashboard';

        add_menu_page(
            'Cart Recovery',
            'Cart Recovery',
            $cap, $slug,
            [ $this, 'render' ],
            'dashicons-cart',
            56
        );
        add_submenu_page( $slug, 'Dashboard',           'Dashboard',           $cap, $slug,                [ $this, 'render' ] );
        add_submenu_page( $slug, 'Follow Up Templates', 'Follow Up Templates', $cap, 'dfca-templates',    [ $this, 'render' ] );
        add_submenu_page( $slug, 'Reports',             'Reports',             $cap, 'dfca-reports',       [ $this, 'render' ] );
        add_submenu_page( $slug, 'Integrations',        'Integrations',        $cap, 'dfca-integrations',  [ $this, 'render' ] );
        add_submenu_page( $slug, 'Settings',            'Settings',            $cap, 'dfca-settings',      [ $this, 'render' ] );
        add_submenu_page( $slug, '⭐ License',          '⭐ License',          'manage_options', 'dfca-license', [ $this, 'render' ] );
        add_submenu_page( $slug, '❓ How to Use',        '❓ How to Use',        $cap, 'dfca-help',     [ $this, 'render' ] );
        add_submenu_page( $slug, '📋 Changelog',         '📋 Changelog',         $cap, 'dfca-changelog',[ $this, 'render' ] );
    }

    public function action_links( $links ) {
        array_unshift( $links,
            '<a href="' . admin_url( 'admin.php?page=dfca-dashboard' ) . '">Dashboard</a>',
            '<a href="' . admin_url( 'admin.php?page=dfca-license' ) . '" style="color:#1a4fa0;font-weight:600;">⭐ License</a>',
            '<a href="mailto:support@dadsfam.co.za">Support</a>'
        );
        return $links;
    }

    public function assets( $hook ) {
        if ( strpos( (string) $hook, 'dfca' ) === false ) return;
        wp_enqueue_style( 'dfca-admin', DFCA_URL . 'assets/admin.css', [], DFCA_VERSION );
        wp_enqueue_script( 'dfca-admin', DFCA_URL . 'assets/admin.js', [ 'jquery' ], DFCA_VERSION, true );
        wp_localize_script( 'dfca-admin', 'DFCA', [
            'rest'    => esc_url_raw( rest_url( 'dfca/v1/' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'isPro'   => dfca_is_premium(),
            'currency'=> get_woocommerce_currency_symbol(),
            'admin_email' => get_option( 'admin_email' ),
            'ajax'        => admin_url( 'admin-ajax.php' ),
            'ajax_nonce'  => wp_create_nonce( 'dfca_action' ),
        ]);
    }

    /* =====================================================
       PAGE ROUTER — DF Licensing header layout
       ===================================================== */
    public function render() {
        $page = sanitize_key( $_GET['page'] ?? 'dfca-dashboard' );
        echo '<div class="dfca-wrap">';
        $this->render_header( $page );
        $this->render_tabs( $page );
        self::flash();
        echo '<div class="dfca-body">';
        // Global upsell bar — shows on every tab when not premium
        if ( ! dfca_is_premium() ) {
            echo '<div class="dfca-upsell" style="margin-bottom:20px;">'
               . '<div><h3>🛒 Love This Plugin? Support Our Work!</h3>'
               . '<p>Get <strong>PRO features, priority support &amp; exclusive updates</strong> by activating a license key. 100% of proceeds support plugin development.</p></div>'
               . '<div class="dfca-upsell-actions"><a href="' . admin_url('admin.php?page=dfca-license') . '" class="dfca-btn dfca-btn-warning">⭐ Unlock PRO Features</a></div>'
               . '</div>';
        }
        switch ( $page ) {
            case 'dfca-templates':    include DFCA_PATH . 'admin/views/templates.php'; break;
            case 'dfca-reports':      include DFCA_PATH . 'admin/views/reports.php';   break;
            case 'dfca-integrations': include DFCA_PATH . 'admin/views/integrations.php'; break;
            case 'dfca-settings':     include DFCA_PATH . 'admin/views/settings.php';  break;
            case 'dfca-license':      include DFCA_PATH . 'admin/views/license.php';   break;
            case 'dfca-help':         include DFCA_PATH . 'admin/views/help.php';     break;
            case 'dfca-changelog':    include DFCA_PATH . 'admin/views/changelog.php'; break;
            default:                  include DFCA_PATH . 'admin/views/dashboard.php'; break;
        }
        echo '</div>';
        $this->render_footer();
        echo '</div>';
    }

    public function render_header( $current ) {
        $pro = dfca_is_premium();
        ?>
        <header class="dfca-top">
            <div class="dfca-brand">
                <div class="dfca-logo">🛒</div>
                <div>
                    <div class="dfca-title">DadsFam Cart Recovery</div>
                    <div class="dfca-subtitle">Win back lost sales — automatically · v<?php echo DFCA_VERSION; ?></div>
                </div>
            </div>
            <div class="dfca-plan">
                <?php if ( $pro ): ?>
                    <span class="dfca-badge dfca-badge-pro">⭐ PRO — Active</span>
                <?php else: ?>
                    <a href="<?php echo admin_url( 'admin.php?page=dfca-license' ); ?>" class="dfca-badge dfca-badge-core">FREE — Upgrade →</a>
                <?php endif; ?>
            </div>
        </header>
        <?php
    }

    public function render_tabs( $current ) {
        $tabs = [
            'dfca-dashboard'    => [ 'Dashboard',           '📊' ],
            'dfca-templates'    => [ 'Follow Up Templates', '📝' ],
            'dfca-reports'      => [ 'Reports',             '📈' ],
            'dfca-integrations' => [ 'Integrations',        '🔌' ],
            'dfca-settings'     => [ 'Settings',            '⚙️' ],
            'dfca-license'      => [ 'License',             '⭐' ],
            'dfca-help'         => [ 'How to Use',         '❓' ],
            'dfca-changelog'    => [ 'Changelog',           '📋' ],
        ];
        ?>
        <nav class="dfca-tabs">
            <?php foreach ( $tabs as $slug => $info ): ?>
                <a href="<?php echo admin_url( 'admin.php?page=' . $slug ); ?>"
                   class="dfca-tab <?php echo $current === $slug ? 'is-active' : ''; ?>">
                    <span><?php echo $info[1]; ?></span><?php echo esc_html( $info[0] ); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    public function render_footer() {
        ?>
        <footer class="dfca-footer">
            DadsFam Cart Recovery v<?php echo DFCA_VERSION; ?> · <a href="https://www.dadsfam.co.za" target="_blank">dadsfam.co.za</a>
        </footer>
        <?php
    }

    /* =====================================================
       POST HANDLERS
       ===================================================== */
    public function handle_post() {
        if ( empty( $_POST['dfca_action'] ) ) return;
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;
        if ( ! check_admin_referer( 'dfca_action' ) ) return;

        $action = sanitize_key( $_POST['dfca_action'] );
        $url    = wp_get_referer() ?: admin_url( 'admin.php?page=dfca-dashboard' );

        switch ( $action ) {

            case 'save_settings':
                update_option( 'dfca_enable_tracking', ! empty( $_POST['enable_tracking'] ) ? 1 : 0 );
                update_option( 'dfca_cutoff_minutes',  max( 1, (int) $_POST['cutoff_minutes'] ) );
                update_option( 'dfca_lost_days',       max( 1, (int) $_POST['lost_days'] ) );
                update_option( 'dfca_disable_for_roles', array_map( 'sanitize_key', (array) ( $_POST['disable_for_roles'] ?? [] ) ) );
                update_option( 'dfca_exclude_statuses',  array_map( 'sanitize_key', (array) ( $_POST['exclude_statuses'] ?? [] ) ) );
                update_option( 'dfca_from_name',     sanitize_text_field( $_POST['from_name'] ?? '' ) );
                update_option( 'dfca_from_email',    sanitize_email( $_POST['from_email'] ?? '' ) );
                update_option( 'dfca_reply_to',      sanitize_email( $_POST['reply_to'] ?? '' ) );
                update_option( 'dfca_coupon_code',   sanitize_text_field( $_POST['coupon_code'] ?? '' ) );
                update_option( 'dfca_unsubscribe_text', sanitize_text_field( $_POST['unsubscribe_text'] ?? '' ) );
                $email_style = in_array( $_POST['email_style'] ?? '', [ 'standalone', 'woocommerce' ], true ) ? $_POST['email_style'] : 'standalone';
                update_option( 'dfca_email_style', $email_style );
                update_option( 'dfca_footer_text', sanitize_text_field( $_POST['footer_text'] ?? '' ) );
                // Dynamic social links — stored as JSON array of {label, url}
                $raw_links   = stripslashes( $_POST['social_links'] ?? '[]' );
                $links_arr   = json_decode( $raw_links, true );
                $clean_links = [];
                if ( is_array( $links_arr ) ) {
                    foreach ( $links_arr as $lnk ) {
                        $label    = sanitize_text_field( $lnk['label'] ?? '' );
                        $link_url = esc_url_raw( $lnk['url'] ?? '' );
                        if ( $label && $link_url ) $clean_links[] = [ 'label' => $label, 'url' => $link_url ];
                    }
                }
                update_option( 'dfca_social_links', wp_json_encode( $clean_links ) );
                wp_safe_redirect( add_query_arg( 'dfca_msg', 'settings_saved', $url ) ); exit;

            case 'save_integration':
                if ( ! dfca_is_premium() ) {
                    wp_safe_redirect( add_query_arg( 'dfca_msg', 'premium_required', $url ) ); exit;
                }
                if ( isset( $_POST['twilio_sid'] ) )    update_option( 'dfca_twilio_sid',   sanitize_text_field( $_POST['twilio_sid'] ) );
                if ( isset( $_POST['twilio_token'] ) )  update_option( 'dfca_twilio_token', sanitize_text_field( $_POST['twilio_token'] ) );
                if ( isset( $_POST['twilio_from'] ) )   update_option( 'dfca_twilio_from',  sanitize_text_field( $_POST['twilio_from'] ) );
                if ( isset( $_POST['wa_phone_id'] ) )   update_option( 'dfca_wa_phone_id',  sanitize_text_field( $_POST['wa_phone_id'] ) );
                if ( isset( $_POST['wa_token'] ) )      update_option( 'dfca_wa_token',     sanitize_text_field( $_POST['wa_token'] ) );
                if ( isset( $_POST['webhook_url'] ) )   update_option( 'dfca_webhook_url',  esc_url_raw( $_POST['webhook_url'] ) );
                wp_safe_redirect( add_query_arg( 'dfca_msg', 'settings_saved', $url ) ); exit;

            case 'save_template':
                $id = (int) ( $_POST['template_id'] ?? 0 );
                $is_premium_channel = in_array( $_POST['channel'] ?? '', [ 'sms', 'whatsapp' ], true );
                if ( $is_premium_channel && ! dfca_is_premium() ) {
                    wp_safe_redirect( add_query_arg( 'dfca_msg', 'premium_required', $url ) ); exit;
                }
                DFCA_Templates::instance()->save( $_POST, $id );
                wp_safe_redirect( add_query_arg( 'dfca_msg', 'template_saved', admin_url( 'admin.php?page=dfca-templates' ) ) ); exit;

            case 'delete_template':
                DFCA_Templates::instance()->delete( (int) $_POST['template_id'] );
                wp_safe_redirect( add_query_arg( 'dfca_msg', 'template_deleted', admin_url( 'admin.php?page=dfca-templates' ) ) ); exit;

            case 'toggle_template':
                $id  = (int) $_POST['template_id'];
                $tpl = DFCA_Templates::instance()->get( $id );
                if ( ! $tpl ) { wp_safe_redirect( $url ); exit; }
                if ( ! $tpl->is_active ) {
                    if ( ! dfca_is_premium() ) {
                        if ( $tpl->channel !== 'email' ) {
                            wp_safe_redirect( add_query_arg( 'dfca_msg', 'premium_required', $url ) ); exit;
                        }
                        if ( $tpl->is_premium ) {
                            wp_safe_redirect( add_query_arg( 'dfca_msg', 'premium_required', $url ) ); exit;
                        }
                        global $wpdb;
                        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dfca_templates WHERE is_active=1 AND channel='email'" );
                        if ( $count >= 1 ) {
                            wp_safe_redirect( add_query_arg( 'dfca_msg', 'free_limit_reached', $url ) ); exit;
                        }
                    }
                }
                DFCA_Templates::instance()->toggle_active( $id, ! $tpl->is_active );
                wp_safe_redirect( $url ); exit;

            case 'duplicate_template':
                $tpl = DFCA_Templates::instance()->get( (int) $_POST['template_id'] );
                if ( $tpl ) {
                    $data = (array) $tpl;
                    unset( $data['id'] );
                    $data['name']     .= ' (copy)';
                    $data['is_active'] = 0;
                    DFCA_Templates::instance()->save( $data );
                }
                wp_safe_redirect( $url ); exit;

            case 'restore_defaults':
                DFCA_Templates::instance()->restore_defaults();
                wp_safe_redirect( add_query_arg( 'dfca_msg', 'defaults_restored', $url ) ); exit;

            case 'save_license':
                if ( ! current_user_can( 'manage_options' ) ) return;
                $key = strtoupper( sanitize_text_field( $_POST['license_key'] ?? '' ) );
                update_option( DFCA_License::OPT_KEY, $key );
                DFCA_License::instance()->clear_cache();
                $ok = DFCA_License::instance()->verify_remote();
                $msg = $ok ? 'license_activated' : 'license_failed';
                wp_safe_redirect( add_query_arg( 'dfca_msg', $msg, admin_url( 'admin.php?page=dfca-license' ) ) ); exit;

            case 'remove_license':
                if ( ! current_user_can( 'manage_options' ) ) return;
                update_option( DFCA_License::OPT_KEY, '' );
                update_option( DFCA_License::OPT_STATUS, 'inactive' );
                update_option( DFCA_License::OPT_MESSAGE, 'License removed.' );
                update_option( DFCA_License::OPT_LOCK_TOKEN, '' );
                DFCA_License::instance()->clear_cache();
                wp_safe_redirect( add_query_arg( 'dfca_msg', 'license_removed', admin_url( 'admin.php?page=dfca-license' ) ) ); exit;

            case 'send_test_email':
                $tpl_id = (int) ( $_POST['template_id'] ?? 0 );
                $to     = sanitize_email( $_POST['send_to'] ?? get_option( 'admin_email' ) );
                if ( ! is_email( $to ) ) {
                    wp_safe_redirect( add_query_arg( 'dfca_msg', 'test_bad_email', $url ) ); exit;
                }
                $result = DFCA_Mailer::instance()->send_test( $tpl_id, $to );
                if ( is_wp_error( $result ) ) {
                    wp_safe_redirect( add_query_arg( [ 'dfca_msg' => 'test_fail', 'dfca_err' => urlencode( $result->get_error_message() ) ], admin_url( 'admin.php?page=dfca-templates' ) ) ); exit;
                }
                wp_safe_redirect( add_query_arg( [ 'dfca_msg' => 'test_ok', 'dfca_to' => urlencode( $to ) ], admin_url( 'admin.php?page=dfca-templates' ) ) ); exit;

            case 'dfca_email_diag':
                if ( ! current_user_can( 'manage_woocommerce' ) ) return;
                $to = sanitize_email( $_POST['diag_email'] ?? get_option( 'admin_email' ) );
                $sent = wp_mail( $to, '[DFCA] Email Connectivity Test', 'If you received this, wp_mail() is working correctly from the DadsFam Cart Recovery plugin.' );
                $msg  = $sent ? 'diag_ok' : 'diag_fail';
                wp_safe_redirect( add_query_arg( [ 'dfca_msg' => $msg, 'diag_to' => urlencode( $to ) ], admin_url( 'admin.php?page=dfca-settings' ) ) ); exit;

            case 'recheck_license':
                if ( ! current_user_can( 'manage_options' ) ) return;
                DFCA_License::instance()->clear_cache();
                DFCA_License::instance()->verify_remote();
                wp_safe_redirect( add_query_arg( 'dfca_msg', 'license_rechecked', admin_url( 'admin.php?page=dfca-license' ) ) ); exit;
        }
    }

    public function ajax_send_test() {
        check_ajax_referer( 'dfca_action' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized', 403 );

        $tpl_id = (int) ( $_POST['template_id'] ?? 0 );
        $email  = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email( $email ) ) wp_send_json_error( [ 'error' => 'Invalid email address.' ] );

        $result = DFCA_Mailer::instance()->send_test( $tpl_id, $email );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'error' => $result->get_error_message() ] );
        }
        wp_send_json_success( [ 'sent_to' => $email ] );
    }

    public static function flash() {
        if ( empty( $_GET['dfca_msg'] ) ) return;
        $map = [
            'settings_saved'      => [ 'success', '✅ Settings saved.' ],
            'template_saved'      => [ 'success', '✅ Template saved.' ],
            'template_deleted'    => [ 'success', '🗑️ Template deleted.' ],
            'defaults_restored'   => [ 'success', '↻ Default templates restored.' ],
            'premium_required'    => [ 'error',   '⭐ This feature requires a Premium license.' ],
            'free_limit_reached'  => [ 'error',   '⚠️ Free tier allows 1 active email template. Upgrade to Premium for unlimited.' ],
            'license_activated'   => [ 'success', '⭐ License activated. Premium features unlocked.' ],
            'license_failed'      => [ 'error',   '❌ License verification failed. Check the key and try again.' ],
            'license_removed'     => [ 'success', '🔓 License removed.' ],
            'license_rechecked'   => [ 'success', '🔄 License re-verified.' ],
            'test_ok'             => [ 'success', '✅ Test email sent to ' . urldecode( $_GET['dfca_to'] ?? '' ) . ' — check your inbox!' ],
            'test_fail'           => [ 'error',   '❌ ' . urldecode( $_GET['dfca_err'] ?? 'wp_mail() failed.' ) ],
            'test_bad_email'      => [ 'error',   '❌ Invalid email address.' ],
            'diag_ok'             => [ 'success', '✅ Test email sent to ' . ( $_GET['diag_to'] ?? '' ) . ' — check your inbox!' ],
            'diag_fail'           => [ 'error',   '❌ wp_mail() returned false. Your SMTP plugin or server cannot send email. Check WP Mail SMTP settings.' ],
        ];
        $msg = $map[ $_GET['dfca_msg'] ] ?? null;
        if ( ! $msg ) return;
        echo '<div class="dfca-flash dfca-flash-' . esc_attr( $msg[0] ) . '">' . esc_html( $msg[1] ) . '</div>';
    }
}
