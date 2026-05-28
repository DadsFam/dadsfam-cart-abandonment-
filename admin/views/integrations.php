<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$is_pro = dfca_is_premium();
?>

<section class="dfca-section">
    <div class="dfca-section-head">
        <h2>Integrations</h2>
    </div>

    <?php if ( ! $is_pro ): ?>
    <div class="dfca-flash dfca-flash-info">
        ⭐ Integrations are a Premium feature. <a href="<?php echo admin_url( 'admin.php?page=dfca-license' ); ?>">Activate your license →</a> to connect SMS, WhatsApp and webhook providers.
    </div>
    <?php endif; ?>

    <div class="dfca-integrations-grid">

        <div class="dfca-integration <?php echo $is_pro ? '' : 'is-locked'; ?>">
            <div class="dfca-integration-head">
                <span class="dfca-integration-icon">📱</span>
                <div>
                    <h3>Twilio SMS</h3>
                    <p>Send SMS reminders to customers who left a phone number at checkout.</p>
                </div>
                <?php if ( ! $is_pro ): ?><span class="dfca-mini-pro">PRO</span><?php endif; ?>
            </div>
            <form method="post" class="dfca-form">
                <?php wp_nonce_field( 'dfca_action' ); ?>
                <input type="hidden" name="dfca_action" value="save_integration">
                <div class="dfca-field">
                    <label>Account SID</label>
                    <input type="text" name="twilio_sid" value="<?php echo esc_attr( get_option( 'dfca_twilio_sid', '' ) ); ?>" <?php disabled( ! $is_pro ); ?>>
                </div>
                <div class="dfca-field">
                    <label>Auth Token</label>
                    <input type="password" name="twilio_token" value="<?php echo esc_attr( get_option( 'dfca_twilio_token', '' ) ); ?>" <?php disabled( ! $is_pro ); ?>>
                </div>
                <div class="dfca-field">
                    <label>From Number</label>
                    <input type="text" name="twilio_from" placeholder="+27..." value="<?php echo esc_attr( get_option( 'dfca_twilio_from', '' ) ); ?>" <?php disabled( ! $is_pro ); ?>>
                </div>
                <button class="dfca-btn dfca-btn-primary" <?php disabled( ! $is_pro ); ?>>Save Twilio Settings</button>
            </form>
        </div>

        <div class="dfca-integration <?php echo $is_pro ? '' : 'is-locked'; ?>">
            <div class="dfca-integration-head">
                <span class="dfca-integration-icon">💬</span>
                <div>
                    <h3>WhatsApp Business</h3>
                    <p>Send recovery messages via Meta WhatsApp Business Cloud API.</p>
                </div>
                <?php if ( ! $is_pro ): ?><span class="dfca-mini-pro">PRO</span><?php endif; ?>
            </div>
            <form method="post" class="dfca-form">
                <?php wp_nonce_field( 'dfca_action' ); ?>
                <input type="hidden" name="dfca_action" value="save_integration">
                <div class="dfca-field">
                    <label>Phone Number ID</label>
                    <input type="text" name="wa_phone_id" value="<?php echo esc_attr( get_option( 'dfca_wa_phone_id', '' ) ); ?>" <?php disabled( ! $is_pro ); ?>>
                </div>
                <div class="dfca-field">
                    <label>Access Token</label>
                    <input type="password" name="wa_token" value="<?php echo esc_attr( get_option( 'dfca_wa_token', '' ) ); ?>" <?php disabled( ! $is_pro ); ?>>
                </div>
                <button class="dfca-btn dfca-btn-primary" <?php disabled( ! $is_pro ); ?>>Save WhatsApp Settings</button>
            </form>
        </div>

        <div class="dfca-integration <?php echo $is_pro ? '' : 'is-locked'; ?>">
            <div class="dfca-integration-head">
                <span class="dfca-integration-icon">🔗</span>
                <div>
                    <h3>Webhook</h3>
                    <p>POST cart recovery events to your CRM, Zapier or Make.com webhook URL.</p>
                </div>
                <?php if ( ! $is_pro ): ?><span class="dfca-mini-pro">PRO</span><?php endif; ?>
            </div>
            <form method="post" class="dfca-form">
                <?php wp_nonce_field( 'dfca_action' ); ?>
                <input type="hidden" name="dfca_action" value="save_integration">
                <div class="dfca-field">
                    <label>Webhook URL</label>
                    <input type="url" name="webhook_url" value="<?php echo esc_attr( get_option( 'dfca_webhook_url', '' ) ); ?>" <?php disabled( ! $is_pro ); ?>>
                </div>
                <button class="dfca-btn dfca-btn-primary" <?php disabled( ! $is_pro ); ?>>Save Webhook</button>
            </form>
        </div>

        <div class="dfca-integration">
            <div class="dfca-integration-head">
                <span class="dfca-integration-icon">✉️</span>
                <div>
                    <h3>SMTP / Email Deliverability</h3>
                    <p>Recovery emails are sent via WordPress <code>wp_mail()</code>. Install an SMTP plugin (e.g. WP Mail SMTP) to use a transactional sender like SendGrid, Mailgun or SES.</p>
                </div>
                <span class="dfca-badge-free">FREE</span>
            </div>
            <p style="margin:0;color:var(--lm-muted);font-size:0.88em">Configure the From / Reply-To addresses on the <a href="<?php echo admin_url( 'admin.php?page=dfca-settings' ); ?>">Settings</a> page.</p>
        </div>

    </div>
</section>
