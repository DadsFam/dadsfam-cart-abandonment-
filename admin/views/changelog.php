<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$changelog = [
    [
        'version' => '1.3.3',
        'date'    => '2026-05-16',
        'type'    => 'minor',
        'title'   => 'Renamed to DadsFam Cart Recovery',
        'changes' => [
            '✨ Plugin renamed from "DadsFam Cart Abandonment" to "DadsFam Cart Recovery" — display strings, menu title, page headers, email text and all UI labels updated.',
            '🧹 Internal slugs, constants (DFCA_), class names, file names and database tables unchanged — existing installs update seamlessly with no data loss.',
            '📧 Support email support@dadsfam.co.za confirmed throughout: plugin action links, How to Use page, and Changelog contact box.',
            '📝 Confirmed plugin is already a multi-file architecture (16 PHP files across includes/, admin/views/, assets/) — no restructuring required.',
        ],
    ],
        [
        'version' => '1.3.2',
        'date'    => '2026-05-16',
        'type'    => 'patch',
        'title'   => 'Settings Redirect Fix & Test Emails Now Styled',
        'changes' => [
            '🐛 FIXED: Saving Settings redirected to the site home page — $url variable was overwritten by the social links foreach loop. Renamed inner variable to $link_url.',
            '🐛 FIXED: Send Test emails were sending plain text only — template rendering had been stripped during a debugging session and never restored. Now sends your full styled HTML template with sample cart data.',
            '🐛 FIXED: File truncation bug in class-dfca-mailer.php caused by an overly aggressive string replacement — all methods (dispatch_email, render_template, wrap_email_body, tracking pixel, unsubscribe) fully restored.',
        ],
    ],
    [
        'version' => '1.3.1',
        'date'    => '2026-05-16',
        'type'    => 'patch',
        'title'   => 'Dynamic Social Links Repeater',
        'changes' => [
            '✨ Email Footer & Social Links section replaced with a fully dynamic repeater — add, name, reorder and remove any number of custom links.',
            '✨ Link labels are free-form text (e.g. "DadsFam Facebook", "Our WhatsApp Group", "Nate Courier Service") — not locked to preset platforms.',
            '✨ Email footer renders links pipe-separated under a "Follow Us" heading, identical to DadsFam Invoice Manager footer style.',
            '🧹 Removed fixed Facebook/Instagram/Twitter/WhatsApp/LinkedIn fields — replaced by the dynamic repeater.',
        ],
    ],
    [
        'version' => '1.2.2',
        'date'    => '2026-05-16',
        'type'    => 'patch',
        'title'   => 'Send Test Now Uses Form POST',
        'changes' => [
            '🐛 FIXED: wp_ajax action was never registered — server returned 0, JS showed generic "Send failed." error.',
            '✨ Send Test is now a plain HTML form POST — works with all SMTP plugins, no AJAX or REST context issues.',
            '✨ Template list: Send Test button opens a lightweight modal with a standard form.',
            '✨ Template edit page: Send Test panel slides open inline, submits as form.',
        ],
    ],
        [
        'version' => '1.3.0',
        'date'    => '2026-05-16',
        'type'    => 'minor',
        'title'   => 'Blue Button, Global Upsell Bar, Email Footer & Social Links',
        'changes' => [
            '✨ Recovery email "Complete Your Purchase" button is now DadsFam blue (#1a4fa0) — run Restore Defaults on templates to update existing ones.',
            '✨ Premium upsell banner now shows on EVERY tab (not just Dashboard/Templates), matching the Image 3 style: light yellow bar, left text, right button.',
            '✨ NEW (PRO): Email footer text customisation — set your own footer message per email.',
            '✨ NEW (PRO): Social media links in email footer — Facebook, Instagram, X/Twitter, WhatsApp, LinkedIn rendered as circular colour buttons.',
            '🧹 Removed duplicate upsell banners from individual pages — now managed globally.',
        ],
    ],
        [
        'version' => '1.2.1',
        'date'    => '2026-05-16',
        'type'    => 'patch',
        'title'   => 'Send Test Fixed — Form POST replaces broken AJAX',
        'changes' => [
            '🐛 FIXED: Send Test was using wp_ajax action that was never registered — server returned 0, JS showed generic "Send failed." regardless of real error.',
            '🐛 FIXED: wp_mail_failed hook now used to capture real error messages instead of unreliable $GLOBALS[phpmailer]->ErrorInfo.',
            '✨ Send Test is now a plain HTML form POST — same stack as every other plugin action, works with all SMTP plugins without any context issues.',
            '✨ Template list: Send Test button opens a lightweight modal with a form that submits normally.',
            '✨ Template edit page: Send Test panel slides open inline, submits as form, result shown as flash message on reload.',
        ],
    ],
        [
        'version' => '1.2.0',
        'date'    => '2026-05-16',
        'type'    => 'patch',
        'title'   => 'Critical Timezone Fix & Email Diagnostics',
        'changes' => [
            '🐛 FIXED: Critical timezone bug — gmdate() (UTC) was compared against updated_at/abandoned_at stored in WordPress local time. On UTC+2 (South Africa), carts needed 2 extra hours before being marked abandoned, preventing any emails from sending.',
            '🐛 FIXED: Template trigger elapsed-time check had same timezone mismatch — now uses get_gmt_from_date() for correct UTC comparison.',
            '✨ NEW: Email Connectivity Test on Settings page — sends a bare plain-text wp_mail() call with zero headers. Instantly confirms whether your SMTP plugin and hosting can send at all.',
            '🧹 Removed "From Name / From Email / Reply-To" fields from Settings — these were overriding SMTP plugin sender settings and causing authentication failures. Sender is now controlled entirely by your SMTP plugin (WP Mail SMTP, FluentSMTP, etc).',
            '🧹 Removed all wp_mail_from / wp_mail_from_name filter hooks — root cause of SMTP authentication errors with certain SMTP plugin configurations.',
        ],
    ],
        [
        'version' => '1.1.0',
        'date'    => '2026-05-15',
        'type'    => 'minor',
        'title'   => 'DF Licensing Style, Force Lock, Preview & Test Send',
        'changes' => [
            '✨ NEW: Full visual restyle to match DadsFam Licensing design system (blue gradient header, white cards, DF colour palette)',
            '✨ NEW: Force Lock receiver at /wp-json/dflm/v1/force-lock-dfca — License Manager can push-suspend instantly',
            '✨ NEW: License re-verified every 15 min on admin page loads + every 4 hours via cron (was 12-hour cache)',
            '✨ NEW: Email Preview — renders template with sample data in a modal iframe',
            '✨ NEW: Send Test Email — fires a [TEST] email to any address directly from the template list',
            '✨ NEW: Run Abandonment Check Now button on Dashboard — manually triggers cron, shows before/after status counts',
            '✨ NEW: System Health card on Dashboard — cron status, active template count, pending/abandoned counts, warnings',
            '✨ NEW: WooCommerce Email Style toggle — wrap recovery emails in store\'s WC template or standalone DadsFam wrapper',
            '✨ NEW: Re-Check Now button on License page for instant re-verification',
            '✨ NEW: Premium upsell banner now uses gold/yellow styling to stand out from content',
            '✅ FIXED: wp_mail_from filter at priority 99 was interfering with SMTP plugins — removed, From passed in headers directly',
            '✅ FIXED: SMTP error messages now include actionable fix steps',
            '✅ FIXED: Integrations save action now uses dedicated save_integration handler',
            '📋 Added How to Use guide and Changelog pages',
        ],
    ],
    [
        'version' => '1.0.0',
        'date'    => '2026-05-14',
        'type'    => 'major',
        'title'   => 'Initial Release',
        'changes' => [
            '✨ NEW: Cart capture via WooCommerce session — email captured on billing field blur (before checkout submit)',
            '✨ NEW: Automated cron every 5 min — promotes pending → abandoned → lost',
            '✨ NEW: Email follow-up templates with merge tags: {customer_name}, {cart_items}, {cart_total}, {recovery_url}, {coupon_code} and more',
            '✨ NEW: Recovery link restores full cart with one click and redirects to checkout',
            '✨ NEW: Open tracking pixel per email',
            '✨ NEW: Unsubscribe link — marks customer unsubscribed instantly',
            '✨ NEW: Dashboard with recovered revenue, recovery rate, date-range filter and line chart',
            '✨ NEW: Follow Up Templates tab — list, create, edit, duplicate, delete, toggle active',
            '✨ NEW: Reports tab — full cart list with status filters and pagination',
            '✨ NEW: Integrations tab — Twilio SMS, WhatsApp Business (Meta Cloud API), Webhook (all Premium)',
            '✨ NEW: Settings tab — tracking toggle, cut-off time, lost time, role exclusions, order status exclusions, coupon code, email sender details',
            '✨ NEW: License tab — activates against DadsFam License Manager via REST API',
            '🔒 PRO: Unlimited email templates (free tier: 1 active)',
            '🔒 PRO: SMS templates via Twilio',
            '🔒 PRO: WhatsApp templates via Meta Cloud API',
            '🔒 PRO: Open rate, click rate, conversion rate analytics',
            '🔒 PRO: Webhooks to Zapier / Make.com / CRM',
            '🔒 PRO: Rich WP editor with media uploads for templates',
        ],
    ],
];

$per_page     = 3;
$total        = count( $changelog );
$total_pages  = (int) ceil( $total / $per_page );
$current_page = max( 1, min( $total_pages, (int) ( $_GET['cl_page'] ?? 1 ) ) );
$page_items   = array_slice( $changelog, ( $current_page - 1 ) * $per_page, $per_page );

$type_cfg = [
    'major' => [ 'label' => 'Major',   'bg' => '#fef3c7', 'color' => '#92400e', 'dot' => 'var(--lm-gold)' ],
    'minor' => [ 'label' => 'Feature', 'bg' => '#dbeafe', 'color' => '#1e40af', 'dot' => 'var(--lm-blue)' ],
    'patch' => [ 'label' => 'Patch',   'bg' => '#d1fae5', 'color' => '#065f46', 'dot' => 'var(--lm-green)' ],
];
?>

<section class="dfca-section">
    <div class="dfca-section-head">
        <h2>📋 Changelog</h2>
        <span class="dfca-badge" style="background:var(--lm-bg);color:var(--lm-text);border:1px solid var(--lm-border);">📦 <?php echo $total; ?> releases</span>
    </div>

    <?php foreach ( $page_items as $entry ):
        $cfg = $type_cfg[ $entry['type'] ] ?? $type_cfg['patch']; ?>
    <div class="dfca-card" style="margin-bottom:16px;border-left:4px solid <?php echo $cfg['dot']; ?>;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;gap:12px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <span style="font-size:22px;font-weight:800;color:var(--lm-text);">v<?php echo esc_html( $entry['version'] ); ?></span>
                <span style="background:<?php echo $cfg['bg']; ?>;color:<?php echo $cfg['color']; ?>;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;"><?php echo $cfg['label']; ?></span>
                <span style="font-size:15px;font-weight:700;color:var(--lm-text);"><?php echo esc_html( $entry['title'] ); ?></span>
            </div>
            <span style="font-size:12px;color:var(--lm-muted);white-space:nowrap;">📅 <?php echo esc_html( $entry['date'] ); ?></span>
        </div>
        <ul style="margin:0;padding:0;list-style:none;">
            <?php foreach ( $entry['changes'] as $change ): ?>
            <li style="padding:6px 0;font-size:13px;color:var(--lm-text);border-bottom:1px solid var(--lm-border);display:flex;align-items:flex-start;gap:10px;">
                <span style="color:<?php echo $cfg['dot']; ?>;font-size:16px;line-height:1.2;flex-shrink:0;">●</span>
                <?php echo esc_html( $change ); ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>

    <?php if ( $total_pages > 1 ):
        $base = admin_url( 'admin.php?page=dfca-changelog&cl_page=' ); ?>
    <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:20px;flex-wrap:wrap;">
        <?php if ( $current_page > 1 ): ?>
            <a href="<?php echo $base . ( $current_page - 1 ); ?>" class="dfca-btn dfca-btn-light">← Previous</a>
        <?php endif; ?>
        <?php for ( $p = 1; $p <= $total_pages; $p++ ): ?>
            <a href="<?php echo $base . $p; ?>" class="dfca-btn" style="<?php echo $p === $current_page ? 'background:var(--lm-blue);color:#fff;' : ''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ( $current_page < $total_pages ): ?>
            <a href="<?php echo $base . ( $current_page + 1 ); ?>" class="dfca-btn dfca-btn-light">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="dfca-card" style="background:#f0f9ff;border-left:4px solid var(--lm-blue);margin-top:18px;">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div style="font-size:32px;">🐛</div>
            <div style="flex:1;">
                <strong style="color:var(--lm-blue);">Found a bug or want to suggest a feature?</strong><br>
                Email <a href="mailto:support@dadsfam.co.za" style="color:var(--lm-blue);font-weight:700;">support@dadsfam.co.za</a> — include your WordPress version, WooCommerce version, and a description of the issue.<br>
                <span style="font-size:0.82em;color:var(--lm-muted);">We aim to respond within 24 hours and ship fixes quickly.</span>
            </div>
        </div>
    </div>
</section>
