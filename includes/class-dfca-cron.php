<?php
/**
 * Cron processor.
 * All datetime comparisons use WordPress local time to match how dates are stored
 * (current_time('mysql') stores local time, not UTC).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_Cron {

    private static $instance = null;
    public static function instance() {
        return self::$instance ?: self::$instance = new self;
    }
    private function __construct() {
        add_action( 'dfca_cron_dispatch', [ $this, 'run' ] );
    }

    public function run() {
        global $wpdb;
        $carts_tbl = $wpdb->prefix . 'dfca_carts';
        $log_tbl   = $wpdb->prefix . 'dfca_email_log';

        $cutoff_min = (int) get_option( 'dfca_cutoff_minutes', 20 );
        $lost_days  = (int) get_option( 'dfca_lost_days', 30 );

        // current_time('timestamp') = time() + WP GMT offset.
        // Dates in the DB are stored via current_time('mysql') = local WP time.
        // We MUST use the same local-time reference for comparisons.
        $now_ts = current_time( 'timestamp' );

        // 1. Pending → abandoned
        $abandoned_before = date( 'Y-m-d H:i:s', $now_ts - ( $cutoff_min * 60 ) );
        $wpdb->query( $wpdb->prepare(
            "UPDATE $carts_tbl SET status='abandoned', abandoned_at=%s
             WHERE status='pending' AND updated_at <= %s",
            current_time( 'mysql' ), $abandoned_before
        ) );

        // 2. Abandoned → lost
        $lost_before = date( 'Y-m-d H:i:s', $now_ts - ( $lost_days * DAY_IN_SECONDS ) );
        $wpdb->query( $wpdb->prepare(
            "UPDATE $carts_tbl SET status='lost'
             WHERE status='abandoned' AND abandoned_at <= %s",
            $lost_before
        ) );

        // 3. Send follow-ups
        $templates = DFCA_Templates::instance()->active( 'email' );
        if ( dfca_is_premium() ) {
            $templates = array_merge(
                $templates,
                DFCA_Templates::instance()->active( 'sms' ),
                DFCA_Templates::instance()->active( 'whatsapp' )
            );
        }
        if ( empty( $templates ) ) return;

        $abandoned_carts = $wpdb->get_results(
            "SELECT * FROM $carts_tbl WHERE status='abandoned' AND user_email <> '' LIMIT 100"
        );

        foreach ( $abandoned_carts as $cart ) {
            // Convert the stored local-time datetime to a UTC timestamp for elapsed-time math.
            $stored_dt = $cart->abandoned_at ?: $cart->updated_at;
            if ( ! $stored_dt ) continue;
            // get_gmt_from_date converts WP local datetime → UTC datetime string.
            $base = strtotime( get_gmt_from_date( $stored_dt ) );
            if ( ! $base ) continue;

            foreach ( $templates as $tpl ) {
                $delay_sec = $this->delay_seconds( $tpl );
                // time() is always UTC — consistent with get_gmt_from_date result.
                if ( ( time() - $base ) < $delay_sec ) continue;

                $already = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM $log_tbl WHERE cart_id=%d AND template_id=%d",
                    $cart->id, $tpl->id
                ) );
                if ( $already ) continue;

                DFCA_Mailer::instance()->send_template( $cart, $tpl );
            }
        }
    }

    private function delay_seconds( $template ) {
        $v = max( 1, (int) $template->trigger_value );
        switch ( $template->trigger_unit ) {
            case 'minutes': return $v * 60;
            case 'hours':   return $v * HOUR_IN_SECONDS;
            case 'days':    return $v * DAY_IN_SECONDS;
        }
        return $v * HOUR_IN_SECONDS;
    }
}
