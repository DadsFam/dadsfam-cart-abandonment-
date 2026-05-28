<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$data    = DFCA_License::instance()->get_status_data();
$active  = $data['status'] === 'active';
?>

<section class="dfca-section">
    <div class="dfca-section-head">
        <h2>⭐ Premium License</h2>
        <?php if ( $active ): ?>
            <span class="dfca-badge dfca-badge-pro" style="background:var(--lm-gold);color:#fff;">PRO — Activated</span>
        <?php else: ?>
            <span class="dfca-badge" style="background:var(--lm-bg);color:var(--lm-text);border:1px solid var(--lm-border);">FREE — Core</span>
        <?php endif; ?>
    </div>

    <div class="dfca-grid2">

        <div class="dfca-card">
            <h3>Your License Key</h3>
            <form method="post">
                <?php wp_nonce_field( 'dfca_action' ); ?>
                <input type="hidden" name="dfca_action" value="save_license">
                <div class="dfca-field">
                    <label>Enter your license key</label>
                    <input type="text" name="license_key" value="<?php echo esc_attr( $data['key'] ); ?>" placeholder="DFCA-XXXX-XXXX-XXXX-XXXX" autocomplete="off" style="font-family:monospace;letter-spacing:1px;">
                    <p class="dfca-help">Purchase a license at <a href="https://www.dadsfam.co.za" target="_blank">dadsfam.co.za</a>. You'll receive your key by email.</p>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="dfca-btn dfca-btn-primary" type="submit">🔑 Activate License</button>
                    <?php if ( $data['key'] ): ?>
                    <button class="dfca-btn dfca-btn-light" type="submit" name="dfca_action" value="recheck_license">🔄 Re-Check Now</button>
                    <button class="dfca-btn dfca-btn-danger" type="submit" name="dfca_action" value="remove_license" onclick="return confirm('Remove the license key from this site?')">🗑️ Remove</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="dfca-card">
            <h3>Status</h3>
            <table class="dfca-status-table">
                <tr><th>Status</th><td>
                    <?php if ( $active ): ?>
                        <span class="dfca-pill dfca-pill-success">✅ Active</span>
                    <?php else: ?>
                        <span class="dfca-pill dfca-pill-error">❌ Inactive</span>
                    <?php endif; ?>
                </td></tr>
                <tr><th>Message</th><td><?php echo esc_html( $data['message'] ?: 'No license activated.' ); ?></td></tr>
                <tr><th>Expires</th><td><?php echo esc_html( $data['expires'] ?: '—' ); ?></td></tr>
                <tr><th>Last Verified</th><td><?php echo esc_html( $data['last_checked'] ?: 'Never' ); ?></td></tr>
                <tr><th>License Server</th><td><code><?php echo esc_html( DFCA_LICENSE_SERVER ); ?></code></td></tr>
                <tr><th>This Site</th><td><code><?php echo esc_html( home_url() ); ?></code></td></tr>
                <tr><th>Force-Lock Endpoint</th><td style="font-size:0.78em;"><code><?php echo esc_html( home_url( '/wp-json/dflm/v1/force-lock-dfca' ) ); ?></code></td></tr>
            </table>
        </div>
    </div>

    <div class="dfca-card">
        <h3>How license enforcement works on this site</h3>
        <ul style="margin:0;padding-left:18px;color:var(--lm-muted);font-size:0.88em;line-height:1.7;">
            <li><strong>Every 15 minutes</strong> (max) while an admin is browsing, the plugin re-verifies the license with dadsfam.co.za. Suspending a key on the LM side locks this site within minutes, not hours.</li>
            <li><strong>Every 4 hours</strong> a scheduled cron job re-verifies even without admin activity.</li>
            <li><strong>Force Lock</strong> from the DadsFam License Manager pings <code>/wp-json/dflm/v1/force-lock-dfca</code> on this site to suspend instantly — no waiting.</li>
            <li>Cache lifetime is <strong>1 hour</strong>, so even if the ping fails, the next admin page load picks up the change.</li>
        </ul>
    </div>

    <div class="dfca-card">
        <h3>What You Unlock With Premium</h3>
        <div class="dfca-features-grid">
            <div class="dfca-feature">
                <div class="dfca-feature-icon">📝</div>
                <h4>Unlimited Email Templates</h4>
                <p>Build multi-step recovery sequences instead of just one reminder.</p>
            </div>
            <div class="dfca-feature">
                <div class="dfca-feature-icon">📱</div>
                <h4>SMS Reminders</h4>
                <p>Send recovery messages via Twilio to phone numbers captured at checkout.</p>
            </div>
            <div class="dfca-feature">
                <div class="dfca-feature-icon">💬</div>
                <h4>WhatsApp Campaigns</h4>
                <p>Reach customers on WhatsApp Business via the Meta Cloud API.</p>
            </div>
            <div class="dfca-feature">
                <div class="dfca-feature-icon">📊</div>
                <h4>Advanced Analytics</h4>
                <p>Open rates, click rates, conversion rates and per-template stats.</p>
            </div>
            <div class="dfca-feature">
                <div class="dfca-feature-icon">🔗</div>
                <h4>Webhooks & Integrations</h4>
                <p>Connect to Zapier, Make.com, your CRM, or any webhook endpoint.</p>
            </div>
            <div class="dfca-feature">
                <div class="dfca-feature-icon">🎨</div>
                <h4>Rich Visual Editor</h4>
                <p>Full WordPress editor with media uploads, instead of the lite text editor.</p>
            </div>
        </div>
    </div>
</section>
