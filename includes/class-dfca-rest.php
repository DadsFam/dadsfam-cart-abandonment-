<?php
/**
 * REST endpoints:
 *   - dfca/v1/...  → admin dashboard AJAX
 *   - dflm/v1/force-lock-dfca → public force-lock receiver from DadsFam License Manager
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_REST {

    private static $instance = null;
    public static function instance() {
        return self::$instance ?: self::$instance = new self;
    }
    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    public function register() {

        /* ===== Force lock receiver — matches DadsFam License Manager's ping URL =====
         * The LM POSTs { token } to this URL when "Force Lock" is clicked.
         * Permission is open because authentication is the lock_token itself,
         * which only the LM knows (it returned it on verify).
         */
        register_rest_route( 'dflm/v1', '/force-lock-dfca', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'args' => [
                'token' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            ],
            'callback' => function( WP_REST_Request $req ) {
                $result = DFCA_License::instance()->receive_force_lock( $req->get_param( 'token' ) );
                if ( is_wp_error( $result ) ) {
                    return new WP_REST_Response( [ 'success' => false, 'message' => $result->get_error_message() ], 403 );
                }
                return new WP_REST_Response( [ 'success' => true, 'message' => 'Plugin locked.' ], 200 );
            },
        ]);

        /* ===== Admin dashboard ===== */
        $perm_admin   = function() { return current_user_can( 'manage_woocommerce' ); };
        $perm_options = function() { return current_user_can( 'manage_options' ); };

        register_rest_route( 'dfca/v1', '/overview', [
            'methods'  => 'GET',
            'permission_callback' => $perm_admin,
            'callback' => function( $req ) {
                return rest_ensure_response( DFCA_Reports::instance()->overview(
                    $req->get_param( 'from' ), $req->get_param( 'to' )
                ));
            },
        ]);

        register_rest_route( 'dfca/v1', '/series', [
            'methods'  => 'GET',
            'permission_callback' => $perm_admin,
            'callback' => function( $req ) {
                return rest_ensure_response( DFCA_Reports::instance()->daily_series(
                    $req->get_param( 'from' ) ?: date( 'Y-m-d', strtotime( '-7 days' ) ),
                    $req->get_param( 'to' )   ?: date( 'Y-m-d' )
                ));
            },
        ]);

        register_rest_route( 'dfca/v1', '/license/verify', [
            'methods'  => 'POST',
            'permission_callback' => $perm_options,
            'callback' => function() {
                DFCA_License::instance()->clear_cache();
                $ok = DFCA_License::instance()->verify_remote();
                return rest_ensure_response( array_merge(
                    [ 'success' => (bool) $ok ],
                    DFCA_License::instance()->get_status_data()
                ));
            },
        ]);

        /* ===== Template preview ===== */
        register_rest_route( 'dfca/v1', '/preview', [
            'methods'  => 'POST',
            'permission_callback' => $perm_admin,
            'callback' => function( WP_REST_Request $req ) {
                $tpl_id = (int) $req->get_param( 'template_id' );
                $tpl    = DFCA_Templates::instance()->get( $tpl_id );
                if ( ! $tpl ) return new WP_REST_Response( [ 'error' => 'Template not found' ], 404 );
                $html = DFCA_Mailer::instance()->preview_html( $tpl );
                return rest_ensure_response( [ 'html' => $html ] );
            },
        ]);

        /* ===== Send test email ===== */
        register_rest_route( 'dfca/v1', '/send-test', [
            'methods'  => 'POST',
            'permission_callback' => $perm_admin,
            'callback' => function( WP_REST_Request $req ) {
                $tpl_id = (int) $req->get_param( 'template_id' );
                $email  = sanitize_email( $req->get_param( 'email' ) );
                if ( ! is_email( $email ) ) return new WP_REST_Response( [ 'error' => 'Invalid email address' ], 400 );
                $result = DFCA_Mailer::instance()->send_test( $tpl_id, $email );
                if ( is_wp_error( $result ) ) {
                    return new WP_REST_Response( [ 'error' => $result->get_error_message() ], 400 );
                }
                return rest_ensure_response( [ 'success' => true, 'sent_to' => $email ] );
            },
        ]);

        /* ===== Manual cron trigger (debug) ===== */
        register_rest_route( 'dfca/v1', '/run-cron', [
            'methods'  => 'POST',
            'permission_callback' => $perm_admin,
            'callback' => function() {
                $before = $this->cart_status_counts();
                DFCA_Cron::instance()->run();
                $after  = $this->cart_status_counts();
                return rest_ensure_response( [
                    'success' => true,
                    'before'  => $before,
                    'after'   => $after,
                ]);
            },
        ]);
    }

    private function cart_status_counts() {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT status, COUNT(*) AS c FROM {$wpdb->prefix}dfca_carts GROUP BY status", OBJECT_K );
        $out = [ 'pending'=>0, 'abandoned'=>0, 'recovered'=>0, 'lost'=>0, 'unsubscribed'=>0 ];
        foreach ( $rows as $s => $r ) $out[ $s ] = (int) $r->c;
        return $out;
    }
}
