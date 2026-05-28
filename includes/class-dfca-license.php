<?php
/**
 * License verification.
 *
 * Architecture (matches DadsFam License Manager v2.x):
 *   1. On verify, the LM returns { valid, message, expires, product, lock_token }
 *   2. We store lock_token silently — client UI never shows it.
 *   3. We expose /wp-json/dflm/v1/force-lock-dfca which the LM pings with that token
 *      to instantly lock this site.
 *   4. Cache is short (1 hr) and we also re-verify on every admin page load.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_License {

    const OPT_KEY        = 'dfca_license_key';
    const OPT_STATUS     = 'dfca_license_status';
    const OPT_MESSAGE    = 'dfca_license_message';
    const OPT_EXPIRES    = 'dfca_license_expires';
    const OPT_CHECKED    = 'dfca_license_last_checked';
    const OPT_LOCK_TOKEN = 'dfca_license_lock_token';
    const TRANS_ACTIVE   = 'dfca_license_active';
    const CACHE_HOURS    = 1; // short — force-lock + admin re-check handle the rest

    private static $instance = null;
    public static function instance() {
        return self::$instance ?: self::$instance = new self;
    }

    private function __construct() {
        // Re-verify every 4 hours via cron
        add_action( 'dfca_license_check', [ $this, 'verify_remote' ] );
        if ( ! wp_next_scheduled( 'dfca_license_check' ) ) {
            wp_schedule_event( time() + 600, 'dfca_every_four_hours', 'dfca_license_check' );
        }

        // Lightly throttled re-verify on admin page loads
        add_action( 'admin_init', [ $this, 'maybe_admin_refresh' ] );
    }

    /* =====================================================
       PUBLIC API
       ===================================================== */
    public function is_active() {
        $cached = get_transient( self::TRANS_ACTIVE );
        if ( $cached === 'yes' ) return true;
        if ( $cached === 'no' )  return false;

        $status = get_option( self::OPT_STATUS, '' );
        if ( $status === 'active' ) {
            $this->verify_remote();
            return get_option( self::OPT_STATUS, '' ) === 'active';
        }
        return false;
    }

    public function get_status_data() {
        return [
            'key'          => get_option( self::OPT_KEY, '' ),
            'status'       => get_option( self::OPT_STATUS, 'inactive' ),
            'message'      => get_option( self::OPT_MESSAGE, '' ),
            'expires'      => get_option( self::OPT_EXPIRES, '' ),
            'last_checked' => get_option( self::OPT_CHECKED, '' ),
        ];
    }

    public function clear_cache() {
        delete_transient( self::TRANS_ACTIVE );
    }

    /**
     * Hit the LM and update local state.
     */
    public function verify_remote() {
        $key = trim( (string) get_option( self::OPT_KEY, '' ) );
        if ( $key === '' ) {
            $this->set_inactive( 'No license key entered.' );
            return false;
        }

        $response = wp_remote_post(
            DFCA_LICENSE_SERVER . DFCA_LICENSE_ENDPOINT,
            [
                'timeout' => 15,
                'body'    => [
                    'license_key' => $key,
                    'site_url'    => home_url(),
                    'plugin_ver'  => DFCA_VERSION,
                    'product'     => DFCA_PRODUCT_CODE,
                ],
            ]
        );

        update_option( self::OPT_CHECKED, current_time( 'mysql' ) );

        if ( is_wp_error( $response ) ) {
            update_option( self::OPT_MESSAGE, 'Could not reach license server: ' . $response->get_error_message() );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) || ! isset( $body['valid'] ) ) {
            $this->set_inactive( 'Invalid response from license server.' );
            return false;
        }

        if ( $body['valid'] ) {
            if ( ! empty( $body['product'] ) && $body['product'] !== DFCA_PRODUCT_CODE ) {
                $this->set_inactive( 'This license key is for a different product (' . esc_html( $body['product'] ) . ').' );
                return false;
            }
            update_option( self::OPT_STATUS,  'active' );
            update_option( self::OPT_MESSAGE, $body['message'] ?? 'License key is valid.' );
            update_option( self::OPT_EXPIRES, $body['expires'] ?? 'never' );

            // Silently capture lock_token for force-lock ping authentication
            if ( ! empty( $body['lock_token'] ) ) {
                update_option( self::OPT_LOCK_TOKEN, sanitize_text_field( $body['lock_token'] ) );
            }

            set_transient( self::TRANS_ACTIVE, 'yes', HOUR_IN_SECONDS * self::CACHE_HOURS );
            return true;
        }

        $this->set_inactive( $body['message'] ?? 'License invalid.' );
        return false;
    }

    private function set_inactive( $message ) {
        update_option( self::OPT_STATUS,  'inactive' );
        update_option( self::OPT_MESSAGE, $message );
        set_transient( self::TRANS_ACTIVE, 'no', HOUR_IN_SECONDS * self::CACHE_HOURS );
    }

    /**
     * Called by DadsFam License Manager via /wp-json/dflm/v1/force-lock-dfca
     */
    public function receive_force_lock( $token ) {
        $stored = get_option( self::OPT_LOCK_TOKEN, '' );
        if ( ! $stored || ! hash_equals( $stored, (string) $token ) ) {
            return new WP_Error( 'invalid_token', 'Invalid token', [ 'status' => 403 ] );
        }
        $this->set_inactive( 'License suspended remotely by DadsFam.' );
        return true;
    }

    /**
     * Throttled re-verify on admin page loads (every 15 min max).
     */
    public function maybe_admin_refresh() {
        if ( ! is_admin() ) return;
        if ( wp_doing_ajax() || wp_doing_cron() ) return;
        if ( ! get_option( self::OPT_KEY, '' ) ) return;

        $last = (int) get_option( 'dfca_license_admin_last', 0 );
        if ( ( time() - $last ) < 15 * MINUTE_IN_SECONDS ) return;
        update_option( 'dfca_license_admin_last', time() );

        $this->clear_cache();
        $this->verify_remote();
    }
}

/* Custom cron interval: every 4 hours */
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['dfca_every_four_hours'] = [ 'interval' => 4 * HOUR_IN_SECONDS, 'display' => 'Every 4 Hours (DFCA)' ];
    return $schedules;
});
