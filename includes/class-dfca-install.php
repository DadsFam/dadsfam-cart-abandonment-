<?php
/**
 * Database tables and default option setup.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_Install {

    public static function maybe_add_unique_key() {
        global $wpdb;
        $log_tbl = $wpdb->prefix . 'dfca_email_log';
        if ( ! $wpdb->get_results( "SHOW INDEX FROM $log_tbl WHERE Key_name = 'unique_cart_tpl'" ) ) {
            // Clean up any existing duplicates first (keep earliest per cart+template)
            $wpdb->query( "DELETE t1 FROM $log_tbl t1 INNER JOIN $log_tbl t2 ON t1.cart_id=t2.cart_id AND t1.template_id=t2.template_id WHERE t1.id > t2.id" );
            $wpdb->query( "ALTER TABLE $log_tbl ADD UNIQUE KEY unique_cart_tpl (cart_id, template_id)" );
        }
    }

    public static function activate() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $carts = $wpdb->prefix . 'dfca_carts';
        $logs  = $wpdb->prefix . 'dfca_email_log';
        $tmpl  = $wpdb->prefix . 'dfca_templates';

        dbDelta( "CREATE TABLE IF NOT EXISTS {$carts} (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id      VARCHAR(64) NOT NULL,
            user_id         BIGINT UNSIGNED DEFAULT 0,
            user_name       VARCHAR(150) DEFAULT '',
            user_email      VARCHAR(190) NOT NULL,
            user_phone      VARCHAR(40) DEFAULT '',
            cart_contents   LONGTEXT NOT NULL,
            cart_total      DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency        VARCHAR(10) DEFAULT 'ZAR',
            recovery_token  VARCHAR(64) NOT NULL,
            status          ENUM('pending','abandoned','recovered','lost','unsubscribed') DEFAULT 'pending',
            order_id        BIGINT UNSIGNED DEFAULT 0,
            recovered_value DECIMAL(12,2) DEFAULT 0,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            abandoned_at    DATETIME DEFAULT NULL,
            recovered_at    DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY recovery_token (recovery_token),
            KEY session_id (session_id),
            KEY user_email (user_email),
            KEY status (status)
        ) $charset;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$logs} (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cart_id      BIGINT UNSIGNED NOT NULL,
            template_id  BIGINT UNSIGNED NOT NULL,
            channel      ENUM('email','sms','whatsapp') DEFAULT 'email',
            sent_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            opened_at    DATETIME DEFAULT NULL,
            clicked_at   DATETIME DEFAULT NULL,
            tracking_id  VARCHAR(64) DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY unique_cart_tpl (cart_id, template_id),
            KEY cart_id (cart_id),
            KEY template_id (template_id),
            KEY tracking_id (tracking_id)
        ) $charset;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$tmpl} (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name          VARCHAR(190) NOT NULL,
            channel       ENUM('email','sms','whatsapp') DEFAULT 'email',
            trigger_value INT NOT NULL DEFAULT 1,
            trigger_unit  ENUM('minutes','hours','days') DEFAULT 'hours',
            subject       VARCHAR(255) DEFAULT '',
            body          LONGTEXT NOT NULL,
            is_active     TINYINT(1) DEFAULT 1,
            is_premium    TINYINT(1) DEFAULT 0,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // Default settings
        $defaults = [
            'enable_tracking'      => 1,
            'cutoff_minutes'       => 20,
            'lost_days'            => 30,
            'disable_for_roles'    => [],
            'exclude_statuses'     => [ 'processing', 'completed' ],
            'from_name'            => get_bloginfo( 'name' ),
            'from_email'           => get_option( 'admin_email' ),
            'reply_to'             => get_option( 'admin_email' ),
            'coupon_code'          => '',
            'unsubscribe_text'     => 'Unsubscribe from these emails',
        ];
        foreach ( $defaults as $k => $v ) {
            if ( get_option( 'dfca_' . $k, null ) === null ) {
                update_option( 'dfca_' . $k, $v );
            }
        }

        // Seed default templates if table is empty
        $exists = $wpdb->get_var( "SELECT COUNT(*) FROM $tmpl" );
        if ( ! $exists ) {
            self::seed_default_templates();
        }

        // Schedule cron
        if ( ! wp_next_scheduled( 'dfca_cron_dispatch' ) ) {
            wp_schedule_event( time() + 60, 'dfca_every_five', 'dfca_cron_dispatch' );
        }
    }

    public static function deactivate() {
        $ts = wp_next_scheduled( 'dfca_cron_dispatch' );
        if ( $ts ) wp_unschedule_event( $ts, 'dfca_cron_dispatch' );
    }

    public static function seed_default_templates() {
        global $wpdb;
        $tmpl = $wpdb->prefix . 'dfca_templates';

        $email_body = '<p>Hi {customer_name},</p>'
            . '<p>We noticed you left some items in your cart at <strong>{site_name}</strong>. '
            . 'Don\'t miss out — your items are still waiting for you.</p>'
            . '<p><strong>Your cart:</strong></p>{cart_items}'
            . '<p><strong>Cart total: {cart_total}</strong></p>'
            . '<p style="text-align:center;margin:30px 0;">'
            . '<a href="{recovery_url}" style="background:#1a4fa0;color:#fff;padding:14px 28px;text-decoration:none;border-radius:8px;display:inline-block;font-weight:600;">Complete Your Purchase</a>'
            . '</p>'
            . '<p>If you have any questions, just reply to this email — we\'re happy to help.</p>'
            . '<p>Thanks,<br>The {site_name} Team</p>'
            . '<p style="font-size:12px;color:#888;text-align:center;margin-top:30px;">'
            . '<a href="{unsubscribe_url}" style="color:#888;">{unsubscribe_text}</a></p>';

        $rows = [
            [
                'name'          => 'First Reminder — Friendly Nudge',
                'channel'       => 'email',
                'trigger_value' => 30,
                'trigger_unit'  => 'minutes',
                'subject'       => 'You left something behind at {site_name} 🛒',
                'body'          => $email_body,
                'is_active'     => 1,
                'is_premium'    => 0,
            ],
            [
                'name'          => 'Second Reminder — 24 Hours Later',
                'channel'       => 'email',
                'trigger_value' => 1,
                'trigger_unit'  => 'days',
                'subject'       => 'Still thinking it over, {customer_name}?',
                'body'          => $email_body,
                'is_active'     => 0,
                'is_premium'    => 1,
            ],
            [
                'name'          => 'Final Reminder — Last Chance',
                'channel'       => 'email',
                'trigger_value' => 3,
                'trigger_unit'  => 'days',
                'subject'       => 'Last chance — your cart expires soon',
                'body'          => $email_body,
                'is_active'     => 0,
                'is_premium'    => 1,
            ],
            [
                'name'          => 'SMS Reminder',
                'channel'       => 'sms',
                'trigger_value' => 1,
                'trigger_unit'  => 'hours',
                'subject'       => '',
                'body'          => 'Hi {customer_name}, you left items in your cart at {site_name}. Complete your order here: {recovery_url}',
                'is_active'     => 0,
                'is_premium'    => 1,
            ],
            [
                'name'          => 'WhatsApp Reminder',
                'channel'       => 'whatsapp',
                'trigger_value' => 2,
                'trigger_unit'  => 'hours',
                'subject'       => '',
                'body'          => 'Hi {customer_name}! 👋 You left {cart_total} worth of goodies in your cart at {site_name}. Tap to finish: {recovery_url}',
                'is_active'     => 0,
                'is_premium'    => 1,
            ],
        ];
        foreach ( $rows as $r ) {
            $wpdb->insert( $tmpl, $r );
        }
    }
}

/* Custom cron interval: every 5 minutes */
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['dfca_every_five'] = [ 'interval' => 300, 'display' => 'Every 5 Minutes (DFCA)' ];
    return $schedules;
});
