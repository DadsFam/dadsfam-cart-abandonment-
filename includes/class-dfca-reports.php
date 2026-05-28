<?php
/**
 * Aggregate reports for the dashboard.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_Reports {

    private static $instance = null;
    public static function instance() {
        return self::$instance ?: self::$instance = new self;
    }
    private function __construct() {}

    public function overview( $from = null, $to = null ) {
        global $wpdb;
        $t = $wpdb->prefix . 'dfca_carts';

        $from = $from ? date( 'Y-m-d 00:00:00', strtotime( $from ) ) : date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
        $to   = $to   ? date( 'Y-m-d 23:59:59', strtotime( $to ) )   : date( 'Y-m-d 23:59:59' );

        $sql = $wpdb->prepare(
            "SELECT
                SUM(CASE WHEN status='recovered' THEN recovered_value ELSE 0 END) AS recovered_revenue,
                SUM(CASE WHEN status='recovered' THEN 1 ELSE 0 END)               AS recovered_orders,
                SUM(CASE WHEN status='abandoned' THEN cart_total ELSE 0 END)      AS recoverable_revenue,
                SUM(CASE WHEN status='abandoned' THEN 1 ELSE 0 END)               AS recoverable_orders,
                SUM(CASE WHEN status='lost' THEN 1 ELSE 0 END)                    AS lost_orders
             FROM $t WHERE created_at BETWEEN %s AND %s",
            $from, $to
        );
        $row = $wpdb->get_row( $sql, ARRAY_A ) ?: [];

        $row = array_map( function( $v ) { return (float) ( $v ?: 0 ); }, $row );

        $total = $row['recovered_orders'] + $row['recoverable_orders'] + $row['lost_orders'];
        $row['recovery_rate'] = $total > 0 ? round( ( $row['recovered_orders'] / $total ) * 100, 2 ) : 0;
        $row['from'] = $from;
        $row['to']   = $to;
        return $row;
    }

    public function daily_series( $from, $to ) {
        global $wpdb;
        $t = $wpdb->prefix . 'dfca_carts';
        $from = date( 'Y-m-d 00:00:00', strtotime( $from ) );
        $to   = date( 'Y-m-d 23:59:59', strtotime( $to ) );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(created_at) AS day,
                    SUM(CASE WHEN status='abandoned' THEN cart_total ELSE 0 END) AS recoverable,
                    SUM(CASE WHEN status='recovered' THEN recovered_value ELSE 0 END) AS recovered
             FROM $t
             WHERE created_at BETWEEN %s AND %s
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            $from, $to
        ));
    }

    public function recent_carts( $limit = 20 ) {
        global $wpdb;
        $t = $wpdb->prefix . 'dfca_carts';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $t ORDER BY id DESC LIMIT %d", $limit
        ));
    }
}
