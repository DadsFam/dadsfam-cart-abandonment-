<?php
/**
 * Cart tracker.
 * - Captures the customer's email at the checkout page (or from logged-in user)
 * - Persists every cart change in {prefix}_dfca_carts as status=pending
 * - When an order is placed, marks cart recovered & links order_id
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_Tracker {

    private static $instance = null;
    public static function instance() {
        return self::$instance ?: self::$instance = new self;
    }

    private function __construct() {
        // Don't track in admin or AJAX-only contexts
        add_action( 'woocommerce_add_to_cart',         [ $this, 'capture_cart' ], 20 );
        add_action( 'woocommerce_cart_item_removed',   [ $this, 'capture_cart' ], 20 );
        add_action( 'woocommerce_cart_item_set_quantity', [ $this, 'capture_cart' ], 20 );
        add_action( 'woocommerce_after_calculate_totals', [ $this, 'capture_cart' ], 20 );

        // Email capture on checkout
        add_action( 'woocommerce_checkout_update_order_review', [ $this, 'capture_email_from_checkout' ] );
        add_action( 'wp_footer', [ $this, 'inject_email_capture_js' ] );
        add_action( 'wp_ajax_dfca_capture_email',        [ $this, 'ajax_capture_email' ] );
        add_action( 'wp_ajax_nopriv_dfca_capture_email', [ $this, 'ajax_capture_email' ] );

        // Recovered: link order to cart
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'on_order_placed' ], 10, 3 );
        add_action( 'woocommerce_thankyou',                  [ $this, 'on_thankyou' ], 10, 1 );

        // Recovery URL handler
        add_action( 'init', [ $this, 'maybe_handle_recovery_link' ] );
    }

    /* =====================================================
       SESSION KEY
       ===================================================== */
    private function session_id() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) return '';
        $sid = WC()->session->get( 'dfca_sid' );
        if ( ! $sid ) {
            $sid = wp_generate_password( 32, false, false );
            WC()->session->set( 'dfca_sid', $sid );
        }
        return $sid;
    }

    private function get_customer_data() {
        $name = $email = $phone = '';

        if ( is_user_logged_in() ) {
            $u = wp_get_current_user();
            $email = $u->user_email;
            $name  = trim( $u->first_name . ' ' . $u->last_name ) ?: $u->display_name;
        }

        if ( function_exists( 'WC' ) && WC()->customer ) {
            $c = WC()->customer;
            if ( ! $email && $c->get_billing_email() ) $email = $c->get_billing_email();
            if ( ! $name ) {
                $fn = $c->get_billing_first_name();
                $ln = $c->get_billing_last_name();
                if ( $fn || $ln ) $name = trim( "$fn $ln" );
            }
            $phone = $c->get_billing_phone();
        }

        // From session (captured via AJAX before login/checkout completed)
        if ( ! $email && WC()->session ) {
            $email = (string) WC()->session->get( 'dfca_email', '' );
            if ( ! $name ) $name = (string) WC()->session->get( 'dfca_name', '' );
        }

        return [
            'name'  => sanitize_text_field( $name ),
            'email' => sanitize_email( $email ),
            'phone' => sanitize_text_field( $phone ),
        ];
    }

    /* =====================================================
       MAIN CAPTURE
       ===================================================== */
    public function capture_cart() {
        if ( is_admin() || wp_doing_cron() ) return;
        if ( ! get_option( 'dfca_enable_tracking', 1 ) ) return;
        if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) return;
        if ( WC()->cart->is_empty() ) return;

        // Skip excluded roles
        if ( is_user_logged_in() ) {
            $excluded = (array) get_option( 'dfca_disable_for_roles', [] );
            $user     = wp_get_current_user();
            if ( array_intersect( $excluded, (array) $user->roles ) ) return;
        }

        $cust = $this->get_customer_data();
        if ( ! is_email( $cust['email'] ) ) return; // no email = nothing to send to

        global $wpdb;
        $table = $wpdb->prefix . 'dfca_carts';
        $sid   = $this->session_id();
        if ( ! $sid ) return;

        $items = [];
        foreach ( WC()->cart->get_cart() as $item ) {
            $product = $item['data'];
            if ( ! $product ) continue;
            $items[] = [
                'id'       => $item['product_id'],
                'name'     => $product->get_name(),
                'qty'      => $item['quantity'],
                'price'    => (float) $product->get_price(),
                'image'    => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                'url'      => get_permalink( $item['product_id'] ),
            ];
        }

        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, recovery_token FROM $table WHERE session_id=%s AND status IN ('pending','abandoned') ORDER BY id DESC LIMIT 1", $sid ) );

        $data = [
            'user_id'       => get_current_user_id(),
            'user_name'     => $cust['name'],
            'user_email'    => $cust['email'],
            'user_phone'    => $cust['phone'],
            'cart_contents' => wp_json_encode( $items ),
            'cart_total'    => (float) WC()->cart->get_total( 'edit' ),
            'currency'      => get_woocommerce_currency(),
            'updated_at'    => current_time( 'mysql' ),
        ];

        if ( $existing ) {
            $wpdb->update( $table, $data, [ 'id' => $existing->id ] );
        } else {
            $data['session_id']     = $sid;
            $data['recovery_token'] = wp_generate_password( 32, false, false );
            $data['status']         = 'pending';
            $data['created_at']     = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
        }
    }

    /* =====================================================
       EMAIL CAPTURE — checkout & AJAX
       ===================================================== */
    public function capture_email_from_checkout( $post_data ) {
        parse_str( $post_data, $data );
        if ( ! empty( $data['billing_email'] ) && WC()->session ) {
            WC()->session->set( 'dfca_email', sanitize_email( $data['billing_email'] ) );
            if ( ! empty( $data['billing_first_name'] ) || ! empty( $data['billing_last_name'] ) ) {
                WC()->session->set( 'dfca_name', trim( ($data['billing_first_name'] ?? '') . ' ' . ($data['billing_last_name'] ?? '') ) );
            }
            $this->capture_cart();
        }
    }

    public function inject_email_capture_js() {
        if ( ! is_checkout() && ! is_cart() ) return;
        if ( ! get_option( 'dfca_enable_tracking', 1 ) ) return;
        ?>
        <script>
        (function(){
            var fired = false;
            function send(email, name){
                if (fired || !email || email.indexOf('@') < 0) return;
                fired = true;
                var fd = new FormData();
                fd.append('action', 'dfca_capture_email');
                fd.append('email', email);
                fd.append('name', name || '');
                fd.append('nonce', '<?php echo wp_create_nonce( 'dfca_capture' ); ?>');
                fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', { method:'POST', body:fd, credentials:'same-origin' });
            }
            document.addEventListener('blur', function(e){
                if (e.target && e.target.matches && e.target.matches('input[name="billing_email"], input[type="email"]')) {
                    var name = '';
                    var fn = document.querySelector('input[name="billing_first_name"]');
                    var ln = document.querySelector('input[name="billing_last_name"]');
                    if (fn) name += fn.value + ' ';
                    if (ln) name += ln.value;
                    send(e.target.value.trim(), name.trim());
                    fired = false; // allow re-send if user edits later
                }
            }, true);
        })();
        </script>
        <?php
    }

    public function ajax_capture_email() {
        if ( ! check_ajax_referer( 'dfca_capture', 'nonce', false ) ) {
            wp_send_json_error( 'bad nonce', 403 );
        }
        $email = sanitize_email( $_POST['email'] ?? '' );
        $name  = sanitize_text_field( $_POST['name'] ?? '' );
        if ( ! is_email( $email ) ) wp_send_json_error( 'invalid email' );
        if ( WC()->session ) {
            WC()->session->set( 'dfca_email', $email );
            if ( $name ) WC()->session->set( 'dfca_name', $name );
        }
        $this->capture_cart();
        wp_send_json_success();
    }

    /* =====================================================
       ORDER PLACED → recovered
       ===================================================== */
    public function on_order_placed( $order_id, $posted_data, $order ) {
        $this->mark_recovered_for_session( $order_id );
    }

    public function on_thankyou( $order_id ) {
        // Fallback in case checkout_order_processed didn't fire (e.g. block-based checkout)
        $this->mark_recovered_for_session( $order_id );
    }

    private function mark_recovered_for_session( $order_id ) {
        if ( ! $order_id || ! function_exists( 'WC' ) || ! WC()->session ) return;
        $sid = WC()->session->get( 'dfca_sid' );
        if ( ! $sid ) return;

        global $wpdb;
        $table = $wpdb->prefix . 'dfca_carts';
        $cart  = $wpdb->get_row( $wpdb->prepare( "SELECT id, cart_total FROM $table WHERE session_id=%s AND status IN ('pending','abandoned') ORDER BY id DESC LIMIT 1", $sid ) );
        if ( ! $cart ) return;

        $order = wc_get_order( $order_id );
        $value = $order ? (float) $order->get_total() : (float) $cart->cart_total;

        $wpdb->update( $table, [
            'status'          => 'recovered',
            'order_id'        => $order_id,
            'recovered_value' => $value,
            'recovered_at'    => current_time( 'mysql' ),
        ], [ 'id' => $cart->id ] );
    }

    /* =====================================================
       RECOVERY LINK → restore cart and redirect
       ===================================================== */
    public function maybe_handle_recovery_link() {
        if ( empty( $_GET['dfca_recover'] ) ) return;
        $token = sanitize_text_field( wp_unslash( $_GET['dfca_recover'] ) );

        global $wpdb;
        $table = $wpdb->prefix . 'dfca_carts';
        $cart  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE recovery_token=%s", $token ) );
        if ( ! $cart || $cart->status === 'recovered' ) return;

        if ( ! function_exists( 'WC' ) ) return;
        if ( null === WC()->session ) WC()->initialize_session();
        if ( null === WC()->cart )    WC()->initialize_cart();

        WC()->cart->empty_cart();
        $items = json_decode( $cart->cart_contents, true ) ?: [];
        foreach ( $items as $i ) {
            if ( ! empty( $i['id'] ) ) {
                WC()->cart->add_to_cart( (int) $i['id'], (int) ( $i['qty'] ?? 1 ) );
            }
        }

        // Log click
        $wpdb->update( $wpdb->prefix . 'dfca_email_log', [
            'clicked_at' => current_time( 'mysql' ),
        ], [ 'cart_id' => $cart->id, 'clicked_at' => null ] );

        wp_safe_redirect( wc_get_checkout_url() );
        exit;
    }
}
