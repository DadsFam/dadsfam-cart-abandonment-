<?php
/**
 * Build & send abandonment recovery emails (and queue SMS/WhatsApp on premium).
 *
 * Two visual modes:
 *   - 'woocommerce' → wraps body in the store's WooCommerce email template,
 *     so recovery emails look identical to order confirmations / shipping notices.
 *   - 'standalone'  → uses our own DadsFam-branded blue-header wrapper.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFCA_Mailer {

    private static $instance = null;
    public static function instance() {
        return self::$instance ?: self::$instance = new self;
    }
    private function __construct() {
        add_action( 'init', [ $this, 'maybe_serve_tracking_pixel' ] );
        // Do NOT add wp_mail_from / wp_mail_from_name filters.
        // Any filter — regardless of priority — can prevent SMTP plugins from using the
        // correct authenticated sender address. The "From" settings in this plugin are used
        // only for the visual email wrapper header text; actual delivery From is controlled
        // entirely by the SMTP plugin (WP Mail SMTP, FluentSMTP, etc).
    }

    /* =====================================================
       PUBLIC: SEND TO A CART
       ===================================================== */
    public function send_template( $cart, $template ) {
        if ( ! $cart || ! $template ) return false;

        if ( in_array( $template->channel, [ 'sms', 'whatsapp' ], true ) && ! dfca_is_premium() ) return false;
        if ( ! empty( $template->is_premium ) && ! dfca_is_premium() ) return false;

        $excluded = (array) get_option( 'dfca_exclude_statuses', [] );
        if ( $cart->order_id && in_array( get_post_status( $cart->order_id ), $excluded, true ) ) return false;
        if ( $cart->status === 'unsubscribed' ) return false;

        global $wpdb;
        $log_table = $wpdb->prefix . 'dfca_email_log';
        $tracking  = wp_generate_password( 32, false, false );
        $wpdb->insert( $log_table, [
            'cart_id'     => $cart->id,
            'template_id' => $template->id,
            'channel'     => $template->channel,
            'sent_at'     => current_time( 'mysql' ),
            'tracking_id' => $tracking,
        ]);
        $log_id = $wpdb->insert_id;

        $merged = $this->render_template( $cart, $template, $tracking );

        if ( $template->channel === 'email' ) {
            return $this->dispatch_email( $cart->user_email, $merged['subject'], $merged['body'], $tracking );
        }
        if ( $template->channel === 'sms' ) {
            do_action( 'dfca_send_sms', $cart, $merged['body'], $template, $log_id );
            return true;
        }
        if ( $template->channel === 'whatsapp' ) {
            do_action( 'dfca_send_whatsapp', $cart, $merged['body'], $template, $log_id );
            return true;
        }
        return false;
    }

    /* =====================================================
       PUBLIC: SEND TEST
       ===================================================== */
    public function send_test( $template_id, $to_email ) {
        $tpl = DFCA_Templates::instance()->get( $template_id );
        if ( ! $tpl ) return new WP_Error( 'not_found', 'Template not found.' );
        if ( $tpl->channel !== 'email' ) return new WP_Error( 'not_email', 'Test sending is only supported for email templates right now.' );

        // Render the full template with sample data — honours email style (DadsFam/WooCommerce)
        $sample  = $this->sample_cart( $to_email );
        $merged  = $this->render_template( $sample, $tpl, 'test-' . wp_generate_password( 8, false, false ) );
        $subject = '[TEST] ' . $merged['subject'];

        // Capture wp_mail errors the correct WP way
        $captured = null;
        $listener = function( $err ) use ( &$captured ) { $captured = $err; };
        add_action( 'wp_mail_failed', $listener );

        $ok = wp_mail( $to_email, $subject, $merged['body'], [ 'Content-Type: text/html; charset=UTF-8' ] );

        remove_action( 'wp_mail_failed', $listener );

        if ( ! $ok ) {
            $msg = ( $captured instanceof WP_Error )
                ? $captured->get_error_message()
                : 'wp_mail() returned false. Check your SMTP plugin settings.';
            return new WP_Error( 'send_failed', $msg );
        }
        return true;
    }


    /* =====================================================
       PUBLIC: PREVIEW HTML
       ===================================================== */
    public function preview_html( $template ) {
        $sample = $this->sample_cart( get_option( 'admin_email' ) );
        $merged = $this->render_template( $sample, $template, 'preview' );
        if ( $template->channel === 'email' ) {
            return $merged['body'];
        }
        return '<div style="font-family:-apple-system,sans-serif;max-width:380px;margin:30px auto;padding:24px;background:#fff;border-radius:18px;border:1px solid #ddd;line-height:1.5;font-size:15px;color:#222;">'
             . '<div style="color:#888;font-size:11px;margin-bottom:10px;text-transform:uppercase;letter-spacing:1px;">' . esc_html( $template->channel ) . '</div>'
             . nl2br( esc_html( $merged['body'] ) )
             . '</div>';
    }

    private function sample_cart( $email ) {
        return (object) [
            'id'              => 0,
            'user_email'      => $email,
            'user_name'       => 'Sample Customer',
            'user_phone'      => '+27 82 000 0000',
            'cart_total'      => 1299.00,
            'currency'        => get_woocommerce_currency(),
            'recovery_token'  => 'SAMPLE-TOKEN',
            'status'          => 'abandoned',
            'cart_contents'   => wp_json_encode( [
                [ 'id'=>0, 'name'=>'Sample Product A', 'qty'=>1, 'price'=>799.00, 'image'=>'', 'url'=>home_url() ],
                [ 'id'=>0, 'name'=>'Sample Product B', 'qty'=>2, 'price'=>250.00, 'image'=>'', 'url'=>home_url() ],
            ]),
        ];
    }

    /* =====================================================
       LOW-LEVEL SEND
       ===================================================== */
    private function dispatch_email( $to, $subject, $body, $tracking ) {
        if ( ! is_email( $to ) ) return false;

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        $reply   = get_option( 'dfca_reply_to', '' );
        if ( is_email( $reply ) ) {
            $headers[] = 'Reply-To: ' . $reply;
        }

        if ( $tracking && $tracking !== 'preview' ) {
            $pixel = add_query_arg( [ 'dfca_open' => $tracking ], home_url( '/' ) );
            $body .= '<img src="' . esc_url( $pixel ) . '" width="1" height="1" style="display:none" alt="">';
        }

        return wp_mail( $to, $subject, $body, $headers );
    }

    /* =====================================================
       RENDERING
       ===================================================== */
    public function render_template( $cart, $template, $tracking_id = '' ) {
        $items = json_decode( $cart->cart_contents, true ) ?: [];

        $items_html = '<table style="width:100%;border-collapse:collapse;margin:15px 0;">';
        foreach ( $items as $i ) {
            $img  = ! empty( $i['image'] ) ? '<img src="' . esc_url( $i['image'] ) . '" width="60" style="border-radius:6px;margin-right:12px;vertical-align:middle;">' : '';
            $items_html .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:10px 0;">' . $img . '<strong>' . esc_html( $i['name'] ) . '</strong><br><span style="color:#888;font-size:13px;">Qty: ' . (int) $i['qty'] . '</span></td><td style="padding:10px 0;text-align:right;color:#333;">' . wc_price( $i['price'] * (int) $i['qty'], [ 'currency' => $cart->currency ] ) . '</td></tr>';
        }
        $items_html .= '</table>';

        $recovery_url = add_query_arg( 'dfca_recover',     $cart->recovery_token, home_url( '/' ) );
        $unsub_url    = add_query_arg( 'dfca_unsubscribe', $cart->recovery_token, home_url( '/' ) );
        $coupon       = trim( (string) get_option( 'dfca_coupon_code', '' ) );

        $replace = [
            '{customer_name}'    => $cart->user_name ?: 'there',
            '{customer_email}'   => $cart->user_email,
            '{cart_items}'       => $items_html,
            '{cart_total}'       => wc_price( $cart->cart_total, [ 'currency' => $cart->currency ] ),
            '{recovery_url}'     => esc_url( $recovery_url ),
            '{unsubscribe_url}'  => esc_url( $unsub_url ),
            '{unsubscribe_text}' => esc_html( get_option( 'dfca_unsubscribe_text', 'Unsubscribe' ) ),
            '{site_name}'        => get_bloginfo( 'name' ),
            '{site_url}'         => home_url(),
            '{coupon_code}'      => $coupon,
        ];

        $subject = strtr( (string) $template->subject, $replace );
        $body    = strtr( (string) $template->body,    $replace );

        if ( $template->channel === 'email' ) {
            $body = $this->wrap_email_body( $body, $subject );
        }
        return [ 'subject' => $subject, 'body' => $body ];
    }

    private function wrap_email_body( $inner, $subject ) {
        $style = get_option( 'dfca_email_style', 'standalone' );

        if ( $style === 'woocommerce' && function_exists( 'WC' ) ) {
            $mailer = WC()->mailer();
            if ( $mailer && method_exists( $mailer, 'wrap_message' ) ) {
                $wrapped = $mailer->wrap_message( $subject, $inner );
                if ( method_exists( $mailer, 'style_inline' ) ) {
                    $wrapped = $mailer->style_inline( $wrapped );
                }
                return $wrapped;
            }
        }

        $site        = get_bloginfo( 'name' );
        $footer_text = esc_html( get_option( 'dfca_footer_text', '' ) ?: ( 'Sent by ' . $site ) );
        $footer_html = $footer_text;

        // Social / custom links (Premium) — pipe-separated, like Image 1
        if ( dfca_is_premium() ) {
            $saved   = json_decode( get_option( 'dfca_social_links', '[]' ), true );
            $anchors = [];
            if ( is_array( $saved ) ) {
                foreach ( $saved as $lnk ) {
                    if ( ! empty( $lnk['label'] ) && ! empty( $lnk['url'] ) ) {
                        $anchors[] = '<a href="' . esc_url( $lnk['url'] ) . '" style="color:#1a4fa0;text-decoration:none;">' . esc_html( $lnk['label'] ) . '</a>';
                    }
                }
            }
            if ( $anchors ) {
                $follow = '<div style="margin-bottom:12px;">'
                        . '<strong style="display:block;margin-bottom:6px;color:#1a2332;">Follow Us</strong>'
                        . implode( ' <span style="color:#aaa;">|</span> ', $anchors )
                        . '</div>';
                $footer_html = $follow . $footer_html;
            }
        }

        return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f0f4f8;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:30px 0;"><tr><td align="center">'
            . '<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.05);">'
            . '<tr><td style="background:linear-gradient(135deg,#1a4fa0,#123a78);padding:24px 30px;color:#fff;font-size:20px;font-weight:600;">' . esc_html( $site ) . '</td></tr>'
            . '<tr><td style="padding:30px;color:#1a2332;line-height:1.6;">' . $inner . '</td></tr>'
            . '<tr><td style="background:#f0f4f8;padding:18px 30px;color:#6b7a8d;font-size:12px;text-align:center;">' . $footer_html . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /* =====================================================
       PIXEL + UNSUBSCRIBE
       ===================================================== */
    public function maybe_serve_tracking_pixel() {
        if ( ! empty( $_GET['dfca_open'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_GET['dfca_open'] ) );
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'dfca_email_log',
                [ 'opened_at' => current_time( 'mysql' ) ],
                [ 'tracking_id' => $token, 'opened_at' => null ]
            );
            header( 'Content-Type: image/gif' );
            echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
            exit;
        }
        if ( ! empty( $_GET['dfca_unsubscribe'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_GET['dfca_unsubscribe'] ) );
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'dfca_carts',
                [ 'status' => 'unsubscribed' ],
                [ 'recovery_token' => $token ]
            );
            wp_die( '<h2>You have been unsubscribed.</h2><p>You will no longer receive cart recovery emails from ' . esc_html( get_bloginfo( 'name' ) ) . '.</p>', 'Unsubscribed', [ 'response' => 200 ] );
        }
    }
}
