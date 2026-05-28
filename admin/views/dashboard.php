<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$reports = DFCA_Reports::instance();
$from    = sanitize_text_field( $_GET['from'] ?? date( 'Y-m-d', strtotime( '-7 days' ) ) );
$to      = sanitize_text_field( $_GET['to']   ?? date( 'Y-m-d' ) );
$ov      = $reports->overview( $from, $to );
$series  = $reports->daily_series( $from, $to );
$recent  = $reports->recent_carts( 5 );
$cur     = get_woocommerce_currency_symbol();

// Quick health checks for debugging
$cron_next   = wp_next_scheduled( 'dfca_cron_dispatch' );
$active_email = (int) DFCA_Templates::instance()->all( 'email' ) ? count( DFCA_Templates::instance()->active( 'email' ) ) : 0;
global $wpdb;
$pending_cnt    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dfca_carts WHERE status='pending'" );
$abandoned_cnt  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dfca_carts WHERE status='abandoned'" );
?>

<section class="dfca-section">
    <div class="dfca-section-head">
        <h2>Overview</h2>
        <form method="get" class="dfca-daterange">
            <input type="hidden" name="page" value="dfca-dashboard">
            <a href="<?php echo admin_url( 'admin.php?page=dfca-dashboard' ); ?>" class="dfca-link-muted">✕ Clear</a>
            <input type="date" name="from" value="<?php echo esc_attr( $from ); ?>">
            <span>—</span>
            <input type="date" name="to"   value="<?php echo esc_attr( $to ); ?>">
            <button class="dfca-btn dfca-btn-light">Apply</button>
        </form>
    </div>

    <div class="dfca-stats">
        <div class="dfca-stat">
            <div class="dfca-stat-label">Recovered Revenue <span class="dfca-info" title="Total value of orders recovered from abandoned carts">ⓘ</span></div>
            <div class="dfca-stat-value"><?php echo $cur . number_format( $ov['recovered_revenue'], 2, ',', ' ' ); ?></div>
        </div>
        <div class="dfca-stat">
            <div class="dfca-stat-label">Recovered Orders</div>
            <div class="dfca-stat-value"><?php echo (int) $ov['recovered_orders']; ?></div>
        </div>
        <div class="dfca-stat">
            <div class="dfca-stat-label">Recoverable Revenue <span class="dfca-info" title="Value still recoverable from currently-abandoned carts">ⓘ</span></div>
            <div class="dfca-stat-value"><?php echo $cur . number_format( $ov['recoverable_revenue'], 2, ',', ' ' ); ?></div>
        </div>
        <div class="dfca-stat">
            <div class="dfca-stat-label">Recoverable Orders</div>
            <div class="dfca-stat-value"><?php echo (int) $ov['recoverable_orders']; ?></div>
        </div>
        <div class="dfca-stat">
            <div class="dfca-stat-label">Lost Orders</div>
            <div class="dfca-stat-value"><?php echo (int) $ov['lost_orders']; ?></div>
        </div>
        <div class="dfca-stat">
            <div class="dfca-stat-label">Recovery Rate</div>
            <div class="dfca-stat-value"><?php echo number_format( $ov['recovery_rate'], 2 ); ?>%</div>
        </div>
    </div>

    <div class="dfca-chart-wrap">
        <div class="dfca-chart-legend">
            <span class="dfca-legend-recoverable">■ Recoverable Revenue</span>
            <span class="dfca-legend-recovered">■ Recovered Revenue</span>
        </div>
        <canvas id="dfca-chart" height="120"
            data-series='<?php echo esc_attr( wp_json_encode( $series ) ); ?>'
            data-from="<?php echo esc_attr( $from ); ?>"
            data-to="<?php echo esc_attr( $to ); ?>"></canvas>
    </div>
</section>

<section class="dfca-section">
    <div class="dfca-section-head">
        <h2>System Health</h2>
        <button id="dfca-run-cron" class="dfca-btn dfca-btn-warning" type="button">🔄 Run Abandonment Check Now</button>
    </div>
    <div class="dfca-card">
        <table class="dfca-status-table">
            <tr>
                <th>Cron next scheduled run</th>
                <td><?php echo $cron_next ? esc_html( date_i18n( 'M j, Y g:i:s a', $cron_next ) . ' (' . human_time_diff( $cron_next ) . ' from now)' ) : '<span style="color:var(--lm-red)">⚠️ Not scheduled — deactivate &amp; reactivate the plugin.</span>'; ?></td>
            </tr>
            <tr>
                <th>Cut-off time (pending → abandoned)</th>
                <td><?php echo (int) get_option( 'dfca_cutoff_minutes', 20 ); ?> minutes</td>
            </tr>
            <tr>
                <th>Pending carts (waiting for cut-off)</th>
                <td><?php echo $pending_cnt; ?> — these will become "abandoned" after the cut-off</td>
            </tr>
            <tr>
                <th>Abandoned carts (will receive emails)</th>
                <td><?php echo $abandoned_cnt; ?></td>
            </tr>
            <tr>
                <th>Active email templates</th>
                <td>
                    <?php echo $active_email; ?>
                    <?php if ( $active_email === 0 ): ?>
                        <span style="color:var(--lm-red);font-weight:600;">⚠️ No active email templates — no recovery emails will be sent. <a href="<?php echo admin_url( 'admin.php?page=dfca-templates' ); ?>">Activate one →</a></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <p class="dfca-help" style="margin-top:14px;">
            <strong>Recovery emails not arriving?</strong> A cart must be older than the cut-off time AND have an email captured (entered at checkout or by a logged-in user) AND have at least one active template whose trigger time has elapsed since the cart was marked abandoned. Use "Run Abandonment Check Now" to force-process immediately for testing.
        </p>
    </div>
</section>

<section class="dfca-section">
    <div class="dfca-section-head">
        <h2>Recent Follow Up Reports</h2>
        <a href="<?php echo admin_url( 'admin.php?page=dfca-reports' ); ?>" class="dfca-link">View All ↗</a>
    </div>
    <?php if ( $recent ): ?>
    <div class="dfca-table-wrap">
        <table class="dfca-table">
            <thead><tr>
                <th>User Name</th><th>Email</th><th>Cart Total</th><th>Order Status</th><th>Date &amp; Time</th>
            </tr></thead>
            <tbody>
            <?php foreach ( $recent as $c ): ?>
                <tr>
                    <td><?php echo esc_html( $c->user_name ?: '—' ); ?></td>
                    <td><?php echo esc_html( $c->user_email ); ?></td>
                    <td><?php echo $cur . number_format( $c->cart_total, 2, ',', ' ' ); ?></td>
                    <td><span class="dfca-status dfca-status-<?php echo esc_attr( $c->status ); ?>"><?php echo esc_html( ucfirst( $c->status ) ); ?></span></td>
                    <td><?php echo esc_html( date_i18n( 'M j, Y g:i a', strtotime( $c->created_at ) ) ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <p class="dfca-empty">No abandoned carts captured yet. Once a customer adds items and enters an email at checkout, they'll appear here.</p>
    <?php endif; ?>
</section>

