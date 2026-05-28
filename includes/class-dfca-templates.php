<?php
/**
 * Follow-up template management.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_Templates {

    private static $instance = null;
    public static function instance() {
        return self::$instance ?: self::$instance = new self;
    }
    private function __construct() {}

    public function table() {
        global $wpdb;
        return $wpdb->prefix . 'dfca_templates';
    }

    public function all( $channel = null ) {
        global $wpdb;
        if ( $channel ) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE channel=%s ORDER BY id ASC", $channel ) );
        }
        return $wpdb->get_results( "SELECT * FROM {$this->table()} ORDER BY channel, id" );
    }

    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id=%d", $id ) );
    }

    public function active( $channel = 'email' ) {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE is_active=1 AND channel=%s ORDER BY id ASC", $channel ) );
        if ( ! dfca_is_premium() ) {
            // FREE: max 1 active email template, no SMS, no WhatsApp
            if ( $channel !== 'email' ) return [];
            $rows = array_slice( $rows, 0, 1 );
            // Also strip any premium-only templates
            $rows = array_filter( $rows, function( $r ) { return empty( $r->is_premium ); } );
        }
        return $rows;
    }

    public function save( $data, $id = 0 ) {
        global $wpdb;
        $clean = [
            'name'          => sanitize_text_field( $data['name'] ?? '' ),
            'channel'       => in_array( $data['channel'] ?? 'email', [ 'email', 'sms', 'whatsapp' ], true ) ? $data['channel'] : 'email',
            'trigger_value' => max( 1, (int) ( $data['trigger_value'] ?? 1 ) ),
            'trigger_unit'  => in_array( $data['trigger_unit'] ?? 'hours', [ 'minutes', 'hours', 'days' ], true ) ? $data['trigger_unit'] : 'hours',
            'subject'       => sanitize_text_field( $data['subject'] ?? '' ),
            'body'          => wp_kses_post( $data['body'] ?? '' ),
            'is_active'     => ! empty( $data['is_active'] ) ? 1 : 0,
            'is_premium'    => ! empty( $data['is_premium'] ) ? 1 : 0,
        ];

        if ( $id ) {
            $wpdb->update( $this->table(), $clean, [ 'id' => $id ] );
            return $id;
        }
        $wpdb->insert( $this->table(), $clean );
        return $wpdb->insert_id;
    }

    public function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table(), [ 'id' => (int) $id ] );
    }

    public function toggle_active( $id, $active ) {
        global $wpdb;
        $wpdb->update( $this->table(), [ 'is_active' => $active ? 1 : 0 ], [ 'id' => (int) $id ] );
    }

    public function restore_defaults() {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$this->table()}" );
        DFCA_Install::seed_default_templates();
    }

    /**
     * Stats per template (sent / opened / clicked / converted).
     * Sent count is shown for everyone; advanced rates are premium-only.
     */
    public function stats( $template_id ) {
        global $wpdb;
        $log = $wpdb->prefix . 'dfca_email_log';
        $carts = $wpdb->prefix . 'dfca_carts';

        $sent     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $log WHERE template_id=%d", $template_id ) );
        $opened   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $log WHERE template_id=%d AND opened_at IS NOT NULL", $template_id ) );
        $clicked  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $log WHERE template_id=%d AND clicked_at IS NOT NULL", $template_id ) );
        $recovered = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT l.cart_id) FROM $log l JOIN $carts c ON c.id=l.cart_id WHERE l.template_id=%d AND c.status='recovered'",
            $template_id
        ));

        return [
            'sent'     => $sent,
            'opens'    => $opened,
            'clicks'   => $clicked,
            'recoveries' => $recovered,
            'open_rate'    => $sent ? round( $opened   * 100 / $sent, 1 ) : 0,
            'click_rate'   => $sent ? round( $clicked  * 100 / $sent, 1 ) : 0,
            'conv_rate'    => $sent ? round( $recovered * 100 / $sent, 1 ) : 0,
        ];
    }
}
