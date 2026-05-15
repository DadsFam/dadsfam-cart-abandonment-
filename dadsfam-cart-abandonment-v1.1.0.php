<?php
/**
 * Plugin Name:       DadsFam Cart Abandonment
 * Plugin URI:        https://www.dadsfam.co.za
 * Description:       Track abandoned WooCommerce carts and send automated recovery emails (free), plus SMS, WhatsApp, multi-step sequences and advanced analytics (premium). Premium features unlock via a DadsFam License Manager key.
 * Version:           1.1.0
 * Author:            DadsFam
 * Author URI:        https://www.dadsfam.co.za
 * License:           GPL v2 or later
 * Text Domain:       dfca
 * Requires PHP:      7.4
 * Requires at least: 5.8
 * WC requires at least: 5.0
 * WC tested up to:   9.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================
   CONSTANTS
   ========================================================= */
define( 'DFCA_VERSION',       '1.1.0' );
define( 'DFCA_FILE',          __FILE__ );
define( 'DFCA_PATH',          plugin_dir_path( __FILE__ ) );
define( 'DFCA_URL',           plugin_dir_url( __FILE__ ) );
define( 'DFCA_BASENAME',      plugin_basename( __FILE__ ) );
define( 'DFCA_PRODUCT_CODE',  'dfca' );

// License server (your dadsfam.co.za site running the DF License Manager plugin)
define( 'DFCA_LICENSE_SERVER', 'https://www.dadsfam.co.za' );
define( 'DFCA_LICENSE_ENDPOINT', '/wp-json/dfem-licenses/v1/verify' );

/* =========================================================
   INCLUDES
   ========================================================= */
require_once DFCA_PATH . 'includes/class-dfca-install.php';
require_once DFCA_PATH . 'includes/class-dfca-license.php';
require_once DFCA_PATH . 'includes/class-dfca-tracker.php';
require_once DFCA_PATH . 'includes/class-dfca-templates.php';
require_once DFCA_PATH . 'includes/class-dfca-mailer.php';
require_once DFCA_PATH . 'includes/class-dfca-cron.php';
require_once DFCA_PATH . 'includes/class-dfca-reports.php';
require_once DFCA_PATH . 'includes/class-dfca-rest.php';

if ( is_admin() ) {
    require_once DFCA_PATH . 'admin/class-dfca-admin.php';
}

/* =========================================================
   ACTIVATION / DEACTIVATION
   ========================================================= */
register_activation_hook( __FILE__, [ 'DFCA_Install', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'DFCA_Install', 'deactivate' ] );

/* =========================================================
   BOOTSTRAP
   ========================================================= */
add_action( 'plugins_loaded', 'dfca_bootstrap' );
function dfca_bootstrap() {

    // WooCommerce required
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>DadsFam Cart Abandonment</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return;
    }

    // Declare WooCommerce HPOS compatibility
    add_action( 'before_woocommerce_init', function() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    });

    // Initialise components
    DFCA_License::instance();
    DFCA_Tracker::instance();
    DFCA_Templates::instance();
    DFCA_Mailer::instance();
    DFCA_Cron::instance();
    DFCA_Reports::instance();
    DFCA_REST::instance();

    if ( is_admin() ) {
        DFCA_Admin::instance();
    }
}

/* =========================================================
   PREMIUM CHECK HELPER (used everywhere)
   ========================================================= */
function dfca_is_premium() {
    return DFCA_License::instance()->is_active();
}
