<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$wc_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : [];
$roles       = wp_roles()->roles;
$excluded    = (array) get_option( 'dfca_exclude_statuses', [ 'processing', 'completed' ] );
$role_excl   = (array) get_option( 'dfca_disable_for_roles', [] );
$email_style = get_option( 'dfca_email_style', 'standalone' );
?>

<form method="post">
    <?php wp_nonce_field( 'dfca_action' ); ?>
    <input type="hidden" name="dfca_action" value="save_settings">

    <section class="dfca-section">
        <h2>General</h2>
        <div class="dfca-card">
            <div class="dfca-setting-row">
                <div>
                    <label class="dfca-setting-label">Enable Tracking</label>
                    <p class="dfca-help">Cart will be considered abandoned if order is not completed in cut-off time.</p>
                </div>
                <label class="dfca-switch">
                    <input type="checkbox" name="enable_tracking" value="1" <?php checked( get_option( 'dfca_enable_tracking', 1 ) ); ?>>
                    <span class="dfca-switch-slider"></span>
                </label>
            </div>

            <div class="dfca-setting-row">
                <div>
                    <label class="dfca-setting-label">Cart Abandoned Cut-Off Time</label>
                    <p class="dfca-help">Consider cart abandoned after this many minutes of item being added to cart and order not placed.</p>
                </div>
                <div class="dfca-input-with-unit">
                    <input type="number" name="cutoff_minutes" min="1" value="<?php echo (int) get_option( 'dfca_cutoff_minutes', 20 ); ?>">
                    <span>minutes</span>
                </div>
            </div>

            <div class="dfca-setting-row">
                <div>
                    <label class="dfca-setting-label">Abandoned Cart Lost Time</label>
                    <p class="dfca-help">Consider cart lost after this many days of item being added to cart and order not placed.</p>
                </div>
                <div class="dfca-input-with-unit">
                    <input type="number" name="lost_days" min="1" value="<?php echo (int) get_option( 'dfca_lost_days', 30 ); ?>">
                    <span>days</span>
                </div>
            </div>

            <div class="dfca-setting-row dfca-setting-row-stack">
                <div>
                    <label class="dfca-setting-label">Disable Tracking For</label>
                    <p class="dfca-help">Selected user roles will be ignored from abandonment processing and will not receive recovery emails.</p>
                </div>
                <select name="disable_for_roles[]" multiple class="dfca-multiselect">
                    <?php foreach ( $roles as $key => $r ): ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php echo in_array( $key, $role_excl, true ) ? 'selected' : ''; ?>><?php echo esc_html( $r['name'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dfca-setting-row dfca-setting-row-stack">
                <div>
                    <label class="dfca-setting-label">Exclude Email Sending For</label>
                    <p class="dfca-help">No future recovery emails will be sent to carts whose linked order matches one of these statuses. The cart will also be marked recovered.</p>
                </div>
                <select name="exclude_statuses[]" multiple class="dfca-multiselect">
                    <?php foreach ( $wc_statuses as $key => $label ):
                        $k = str_replace( 'wc-', '', $key ); ?>
                        <option value="<?php echo esc_attr( $k ); ?>" <?php echo in_array( $k, $excluded, true ) ? 'selected' : ''; ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dfca-setting-row">
                <div>
                    <label class="dfca-setting-label">Recovery Coupon Code <em>(optional)</em></label>
                    <p class="dfca-help">A WooCommerce coupon code to include in your recovery emails via the <code>{coupon_code}</code> merge tag.</p>
                </div>
                <input type="text" name="coupon_code" value="<?php echo esc_attr( get_option( 'dfca_coupon_code', '' ) ); ?>" placeholder="SAVE10" style="max-width:240px;">
            </div>
        </div>
    </section>

    <section class="dfca-section">
        <h2>Email</h2>
        <div class="dfca-card">

            <div class="dfca-setting-row dfca-setting-row-stack">
                <div>
                    <label class="dfca-setting-label">Email Style</label>
                    <p class="dfca-help">Choose how your recovery emails look. <strong>WooCommerce style</strong> wraps your content in your store's existing email template (matching order confirmations, shipping notices etc.). <strong>DadsFam style</strong> uses our own blue-branded wrapper.</p>
                </div>
                <div style="display:flex;gap:18px;flex-wrap:wrap;">
                    <label style="display:flex;align-items:center;gap:8px;padding:14px 18px;border:2px solid <?php echo $email_style === 'standalone' ? 'var(--lm-blue)' : 'var(--lm-border)'; ?>;border-radius:10px;cursor:pointer;background:#fff;flex:1;min-width:200px;">
                        <input type="radio" name="email_style" value="standalone" <?php checked( $email_style, 'standalone' ); ?>>
                        <div>
                            <strong style="display:block;">DadsFam Blue (default)</strong>
                            <span style="color:var(--lm-muted);font-size:0.82em;">Our branded wrapper</span>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;padding:14px 18px;border:2px solid <?php echo $email_style === 'woocommerce' ? 'var(--lm-blue)' : 'var(--lm-border)'; ?>;border-radius:10px;cursor:pointer;background:#fff;flex:1;min-width:200px;">
                        <input type="radio" name="email_style" value="woocommerce" <?php checked( $email_style, 'woocommerce' ); ?>>
                        <div>
                            <strong style="display:block;">WooCommerce Template</strong>
                            <span style="color:var(--lm-muted);font-size:0.82em;">Matches store transactional emails</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="dfca-field">
                <label class="dfca-setting-label">Unsubscribe Link Text</label>
                <p class="dfca-help">Text shown for the unsubscribe link in the email footer.</p>
                <input type="text" name="unsubscribe_text" value="<?php echo esc_attr( get_option( 'dfca_unsubscribe_text', 'Unsubscribe from these emails' ) ); ?>">
            </div>

            <div style="background:#f0f4f8;border-radius:10px;padding:18px;margin-top:8px;border:1px solid var(--lm-border);">
                <p class="dfca-help" style="margin:0 0 6px;"><strong>From / Reply-To</strong> are controlled by your SMTP plugin (WP Mail SMTP, FluentSMTP, etc). Configure sender details there to avoid authentication conflicts.</p>
            </div>
        </div>
    </section>

    <section class="dfca-section">
        <h2>Email Footer &amp; Social Links <span class="dfca-mini-pro">PRO</span></h2>
        <div class="dfca-card">
            <?php if ( ! dfca_is_premium() ): ?>
            <div class="dfca-flash dfca-flash-info" style="margin:0 0 16px;">⭐ Activate a Premium license to customise your email footer and add social media links. <a href="<?php echo admin_url('admin.php?page=dfca-license'); ?>">Unlock PRO →</a></div>
            <?php endif; ?>
            <div class="dfca-field">
                <label class="dfca-setting-label">Footer Text</label>
                <p class="dfca-help">Shown at the bottom of every recovery email. Defaults to "Sent by {site name}".</p>
                <input type="text" name="footer_text" value="<?php echo esc_attr( get_option('dfca_footer_text','') ); ?>" placeholder="Sent by <?php echo esc_attr( get_bloginfo('name') ); ?>" <?php disabled( ! dfca_is_premium() ); ?>>
            </div>
            <label class="dfca-setting-label" style="margin-bottom:6px;">Social &amp; Custom Links</label>
            <p class="dfca-help" style="margin-bottom:14px;">Add any links you like — name them whatever you want. They render in the email footer as: <strong>Link One | Link Two | Link Three</strong></p>

            <div id="dfca-social-rows" style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;">
                <?php
                $saved_links = json_decode( get_option( 'dfca_social_links', '[]' ), true );
                if ( ! is_array( $saved_links ) ) $saved_links = [];
                foreach ( $saved_links as $lnk ): ?>
                <div class="dfca-social-row" style="display:flex;gap:8px;align-items:center;">
                    <input type="text" class="dfca-link-label" placeholder="Label (e.g. DadsFam Facebook)" value="<?php echo esc_attr( $lnk['label'] ?? '' ); ?>" style="flex:1;padding:9px 12px;border:1px solid var(--lm-border);border-radius:8px;font-size:0.88em;" <?php disabled( ! dfca_is_premium() ); ?>>
                    <input type="url"  class="dfca-link-url"   placeholder="https://..." value="<?php echo esc_url( $lnk['url'] ?? '' ); ?>" style="flex:2;padding:9px 12px;border:1px solid var(--lm-border);border-radius:8px;font-size:0.88em;" <?php disabled( ! dfca_is_premium() ); ?>>
                    <?php if ( dfca_is_premium() ): ?>
                    <button type="button" class="dfca-remove-social-row" title="Remove" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;border-radius:8px;width:34px;height:34px;font-size:18px;cursor:pointer;flex-shrink:0;line-height:1;">×</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ( dfca_is_premium() ): ?>
            <button type="button" id="dfca-add-social-row" class="dfca-btn dfca-btn-light" style="margin-bottom:4px;">+ Add Link</button>
            <?php endif; ?>
            <input type="hidden" name="social_links" id="dfca-social-links-json" value="<?php echo esc_attr( get_option('dfca_social_links','[]') ); ?>">

            <script>
            (function(){
                var isPro = <?php echo dfca_is_premium() ? 'true' : 'false'; ?>;
                if (!isPro) return;

                function makeRow(label, url) {
                    var row = document.createElement('div');
                    row.className = 'dfca-social-row';
                    row.style.cssText = 'display:flex;gap:8px;align-items:center;';
                    row.innerHTML =
                        '<input type="text" class="dfca-link-label" placeholder="Label (e.g. DadsFam Facebook)" value="' + (label||'') + '" style="flex:1;padding:9px 12px;border:1px solid var(--lm-border);border-radius:8px;font-size:0.88em;">' +
                        '<input type="url" class="dfca-link-url" placeholder="https://..." value="' + (url||'') + '" style="flex:2;padding:9px 12px;border:1px solid var(--lm-border);border-radius:8px;font-size:0.88em;">' +
                        '<button type="button" class="dfca-remove-social-row" title="Remove" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;border-radius:8px;width:34px;height:34px;font-size:18px;cursor:pointer;flex-shrink:0;line-height:1;">×</button>';
                    row.querySelector('.dfca-remove-social-row').addEventListener('click', function(){ row.remove(); });
                    return row;
                }

                // Wire up existing remove buttons
                document.querySelectorAll('.dfca-remove-social-row').forEach(function(btn){
                    btn.addEventListener('click', function(){ btn.closest('.dfca-social-row').remove(); });
                });

                // Add row button
                document.getElementById('dfca-add-social-row').addEventListener('click', function(){
                    document.getElementById('dfca-social-rows').appendChild(makeRow('',''));
                });

                // On form submit, serialize rows to JSON
                var form = document.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function(){
                        var links = [];
                        document.querySelectorAll('#dfca-social-rows .dfca-social-row').forEach(function(row){
                            var lbl = (row.querySelector('.dfca-link-label').value || '').trim();
                            var url = (row.querySelector('.dfca-link-url').value  || '').trim();
                            if (lbl && url) links.push({label: lbl, url: url});
                        });
                        document.getElementById('dfca-social-links-json').value = JSON.stringify(links);
                    });
                }
            })();
            </script>
        </div>
    </section>

    <div class="dfca-form-actions">
        <button type="submit" class="dfca-btn dfca-btn-primary">💾 Save All Settings</button>
    </div>
</form>

<section class="dfca-section" style="margin-top:6px;">
    <div class="dfca-section-head"><h2>📧 Email Connectivity Test</h2></div>
    <div class="dfca-card">
        <p style="margin:0 0 14px;font-size:0.9em;color:var(--lm-muted);">This sends the most basic possible <code>wp_mail()</code> call — plain text, no headers, no HTML. If this works, email delivery is functional and the Send Test on your templates will work too. If this fails, your SMTP plugin or hosting needs attention.</p>
        <form method="post">
            <?php wp_nonce_field( 'dfca_action' ); ?>
            <input type="hidden" name="dfca_action" value="dfca_email_diag">
            <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                <div style="flex:1;min-width:220px;">
                    <label style="font-size:0.88em;font-weight:600;display:block;margin-bottom:4px;">Send to</label>
                    <input type="email" name="diag_email" value="<?php echo esc_attr( get_option('admin_email') ); ?>" style="width:100%;padding:9px 13px;border:1px solid var(--lm-border);border-radius:8px;font-size:0.9em;">
                </div>
                <button type="submit" class="dfca-btn dfca-btn-primary">📤 Send Connectivity Test</button>
            </div>
        </form>
    </div>
</section>
