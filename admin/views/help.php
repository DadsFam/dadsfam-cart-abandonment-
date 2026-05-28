<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<section class="dfca-section">
    <div class="dfca-card" style="border-left:4px solid var(--lm-blue);">
        <h2 style="border:0;padding:0;margin-bottom:18px;">🎯 Quick Start — First Email in 5 Minutes</h2>
        <div style="font-size:14px;line-height:1.9;color:var(--lm-text);">
            <p><strong>Step 1 — Check tracking is on</strong></p>
            <ul style="padding-left:20px;margin-bottom:14px;">
                <li>Go to <strong>Cart Recovery → Settings</strong></li>
                <li>Make sure <strong>Enable Tracking</strong> is toggled on</li>
                <li>Set <strong>Cut-Off Time</strong> (default 20 min — lower it to 1 min for testing)</li>
                <li>Fill in <strong>From Name</strong> and <strong>From Email</strong> so emails don't land in spam</li>
            </ul>

            <p><strong>Step 2 — Activate an email template</strong></p>
            <ul style="padding-left:20px;margin-bottom:14px;">
                <li>Go to <strong>Cart Recovery → Follow Up Templates</strong></li>
                <li>Click the toggle on <strong>"First Reminder — Friendly Nudge"</strong> to turn it on</li>
                <li>Optionally click ✏️ to edit the subject, body, or trigger time</li>
            </ul>

            <p><strong>Step 3 — Test it</strong></p>
            <ul style="padding-left:20px;margin-bottom:14px;">
                <li>On the template list, click ✉️ <strong>Send Test</strong> to fire a sample email to yourself immediately</li>
                <li>Open an incognito window → add a product → go to checkout → type your email in the billing field → close the tab</li>
                <li>Wait for the cut-off time to pass, then go to <strong>Dashboard → Run Abandonment Check Now</strong></li>
                <li>✅ Recovery email sent! Check your inbox (and spam folder)</li>
            </ul>

            <p><strong>That's it — you're recovering carts.</strong></p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;margin-top:4px;">

        <div class="dfca-card">
            <h2>🛒 How Cart Capture Works</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p>A cart is only captured when an <strong>email address is available</strong>. This happens one of three ways:</p>
                <ul style="padding-left:20px;">
                    <li><strong>Logged-in user</strong> — captured instantly when they add to cart</li>
                    <li><strong>Checkout email field</strong> — captured as soon as they type and leave the billing email field (before submitting)</li>
                    <li><strong>Previous WC session</strong> — if WooCommerce already knows their email</li>
                </ul>
                <p style="margin-top:10px;">Guests who add to cart but never reach the checkout email field are <strong>not captured</strong> — there is no email to send to.</p>
            </div>
        </div>

        <div class="dfca-card">
            <h2>⏱️ The Abandonment Timeline</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p>Each cart moves through these states automatically:</p>
                <ul style="padding-left:20px;">
                    <li><span class="dfca-status dfca-status-pending">Pending</span> — cart captured, waiting for cut-off time</li>
                    <li><span class="dfca-status dfca-status-abandoned">Abandoned</span> — cut-off elapsed, recovery emails will fire</li>
                    <li><span class="dfca-status dfca-status-recovered">Recovered</span> — customer clicked the link and completed order</li>
                    <li><span class="dfca-status dfca-status-lost">Lost</span> — abandoned longer than the "Lost Time" setting</li>
                </ul>
                <p style="margin-top:10px;font-size:12px;color:var(--lm-muted);">The cron runs every 5 minutes. Use <strong>Dashboard → Run Abandonment Check Now</strong> to trigger it instantly during testing.</p>
            </div>
        </div>

        <div class="dfca-card">
            <h2>📝 Managing Templates</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p>Go to <strong>Follow Up Templates → Email</strong>. Each template has:</p>
                <ul style="padding-left:20px;">
                    <li><strong>Trigger</strong> — how long after abandonment to send (e.g. 30 min, 1 day, 3 days)</li>
                    <li><strong>Subject + Body</strong> — use merge tags like <code>{customer_name}</code>, <code>{recovery_url}</code>, <code>{cart_total}</code></li>
                    <li><strong>Active toggle</strong> — only active templates are sent</li>
                    <li>👁️ <strong>Preview</strong> — see how it looks with sample data</li>
                    <li>✉️ <strong>Send Test</strong> — fire to any inbox instantly</li>
                </ul>
                <p style="margin-top:10px;font-size:12px;color:var(--lm-muted);">Free tier: 1 active email template. Premium: unlimited + SMS + WhatsApp.</p>
            </div>
        </div>

        <div class="dfca-card">
            <h2>🔗 The Recovery Link</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p>Every email contains a <code>{recovery_url}</code> button. When your customer clicks it:</p>
                <ul style="padding-left:20px;">
                    <li>Their cart is automatically restored with the exact same items</li>
                    <li>They land on the checkout page, ready to complete the order</li>
                    <li>The cart is marked <strong>Recovered</strong> when they place the order</li>
                    <li>The click is logged for analytics (Premium)</li>
                </ul>
                <p style="margin-top:10px;">Each link is unique and single-use per cart. Expired carts redirect to checkout with an empty cart.</p>
            </div>
        </div>

        <div class="dfca-card">
            <h2>✉️ Email Style Options</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p>Go to <strong>Settings → Email → Email Style</strong> to choose:</p>
                <ul style="padding-left:20px;">
                    <li><strong>DadsFam Blue</strong> — our clean branded wrapper with blue header (default)</li>
                    <li><strong>WooCommerce Template</strong> — wraps in your store's existing WC email design so recovery emails match order confirmations, shipping notices, etc.</li>
                </ul>
                <p style="margin-top:10px;">If emails aren't sending, install <strong>WP Mail SMTP</strong> (free) and connect it to Gmail, Brevo, Mailgun or SendGrid for reliable delivery.</p>
            </div>
        </div>

        <div class="dfca-card">
            <h2>🎟️ Coupon Codes</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p>You can include a WooCommerce coupon code in your recovery emails to incentivise completion:</p>
                <ul style="padding-left:20px;">
                    <li>Go to <strong>Settings → General → Recovery Coupon Code</strong></li>
                    <li>Enter a valid WooCommerce coupon code (create it in <strong>WooCommerce → Coupons</strong> first)</li>
                    <li>Add <code>{coupon_code}</code> anywhere in your email template body</li>
                </ul>
                <p style="margin-top:10px;font-size:12px;color:var(--lm-muted);">Tip: Use a percentage discount on the last reminder (e.g. day 3) for maximum impact.</p>
            </div>
        </div>

        <div class="dfca-card">
            <h2>📱 SMS &amp; WhatsApp (Premium)</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p>With a Premium license you can add SMS and WhatsApp recovery messages alongside email:</p>
                <ul style="padding-left:20px;">
                    <li>Go to <strong>Integrations → Twilio SMS</strong> — enter your Twilio credentials</li>
                    <li>Go to <strong>Integrations → WhatsApp Business</strong> — enter Meta Cloud API credentials</li>
                    <li>Then create SMS or WhatsApp templates in <strong>Follow Up Templates → SMS / WhatsApp</strong></li>
                </ul>
                <p style="margin-top:10px;font-size:12px;color:var(--lm-muted);">Phone numbers are captured from the billing phone field at checkout.</p>
            </div>
        </div>

        <div class="dfca-card">
            <h2>⭐ Premium License</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p>Go to <strong>Cart Recovery → License</strong> and enter your key to unlock:</p>
                <ul style="padding-left:20px;">
                    <li>Unlimited email templates &amp; multi-step sequences</li>
                    <li>SMS recovery via Twilio</li>
                    <li>WhatsApp recovery via Meta Cloud API</li>
                    <li>Open rate, click rate &amp; conversion analytics</li>
                    <li>Webhooks for Zapier, Make.com, or your CRM</li>
                    <li>Rich visual editor with media uploads</li>
                </ul>
                <p style="margin-top:10px;font-size:12px;color:var(--lm-muted);">Licenses are managed from <a href="https://dadsfam.co.za" target="_blank" style="color:var(--lm-blue);">dadsfam.co.za</a>. Contact us to purchase.</p>
            </div>
        </div>

        <div class="dfca-card">
            <h2>❓ Common Questions</h2>
            <div style="font-size:13px;line-height:1.7;color:var(--lm-text);">
                <p><strong>Q: Emails aren't sending — why?</strong><br>
                A: Three things must all be true: (1) an email was captured, (2) the cart is older than the cut-off, (3) an active template's trigger time has elapsed. Use <strong>Dashboard → Run Abandonment Check Now</strong> to confirm. If wp_mail itself fails, install WP Mail SMTP.</p>

                <p><strong>Q: The cart shows as Pending but not Abandoned?</strong><br>
                A: The cut-off time hasn't passed yet. Lower it in Settings for testing, or click "Run Abandonment Check Now".</p>

                <p><strong>Q: Will returning customers get emails every time?</strong><br>
                A: Only if they start a new cart session. Each unique session generates one set of emails.</p>

                <p><strong>Q: Can customers unsubscribe?</strong><br>
                A: Yes — the <code>{unsubscribe_url}</code> tag adds a link that marks them unsubscribed instantly.</p>
            </div>
        </div>

    </div>

    <div class="dfca-card" style="background:#f0f9ff;border-left:4px solid var(--lm-blue);margin-top:4px;">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div style="font-size:36px;">💬</div>
            <div style="flex:1;">
                <strong style="color:var(--lm-blue);font-size:1em;">Need help or have a feature request?</strong><br>
                Email us at <a href="mailto:support@dadsfam.co.za" style="color:var(--lm-blue);font-weight:700;">support@dadsfam.co.za</a> — we usually respond within 24 hours.<br>
                <span style="font-size:0.84em;color:var(--lm-muted);">Visit <a href="https://dadsfam.co.za" target="_blank" style="color:var(--lm-blue);">dadsfam.co.za</a> for documentation, license purchases and news.</span>
            </div>
        </div>
    </div>
</section>
