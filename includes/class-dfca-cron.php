<?php
/**
 * Cron processor.
 * - Transient lock prevents concurrent runs (multiple simultaneous page loads
 *   can each trigger wp-cron; without a lock they all send duplicate emails).
 * - Database unique key on email_log (cart_id, template_id) is a second safety
 *   net: even if the lock fails, only one INSERT can win at the DB level.
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
        // ── Cron lock ──────────────────────────────────────────────────────
        // Prevents two simultaneous PHP processes both running the cron.
        // 90-second TTL: if the process dies the lock auto-expires.
        if ( get_transient( 'dfca_cron_lock' ) ) return;
        set_transient( 'dfca_cron_lock', 1, 90 );

        try {
            $this->process();
        } finally {
            delete_transient( 'dfca_cron_lock' );
        }
    }

    private function process() {
        global $wpdb;
        $carts_tbl = $wpdb->prefix . 'dfca_carts';
        $log_tbl   = $wpdb->prefix . 'dfca_email_log';

        $cutoff_min = (int) get_option( 'dfca_cutoff_minutes', 20 );
        $lost_days  = (int) get_option( 'dfca_lost_days', 30 );
        $now_ts     = current_time( 'timestamp' );

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
            $stored_dt = $cart->abandoned_at ?: $cart->updated_at;
            if ( ! $stored_dt ) continue;
            $base = strtotime( get_gmt_from_date( $stored_dt ) );
            if ( ! $base ) continue;

            foreach ( $templates as $tpl ) {
                $delay_sec = $this->delay_seconds( $tpl );
                if ( ( time() - $base ) < $delay_sec ) continue;

                // ── Deduplication check ────────────────────────────────────
                // Note: send_template() also uses INSERT IGNORE on the unique
                // DB key, so even a race condition here is caught at DB level.
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
