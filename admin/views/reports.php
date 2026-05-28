<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$t = $wpdb->prefix . 'dfca_carts';

$status_filter = sanitize_key( $_GET['status'] ?? '' );
$where = '1=1';
if ( in_array( $status_filter, [ 'pending', 'abandoned', 'recovered', 'lost', 'unsubscribed' ], true ) ) {
    $where = $wpdb->prepare( "status=%s", $status_filter );
}
$paged   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per     = 25;
$offset  = ( $paged - 1 ) * $per;

$rows    = $wpdb->get_results( "SELECT * FROM $t WHERE $where ORDER BY id DESC LIMIT $per OFFSET $offset" );
$total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE $where" );
$pages   = max( 1, (int) ceil( $total / $per ) );
$cur     = get_woocommerce_currency_symbol();

$counts = $wpdb->get_results( "SELECT status, COUNT(*) c FROM $t GROUP BY status", OBJECT_K );
$count_for = function( $s ) use ( $counts ) { return isset( $counts[ $s ] ) ? (int) $counts[ $s ]->c : 0; };
?>

<section class="dfca-section">
    <div class="dfca-section-head">
        <h2>Follow Up Reports</h2>
    </div>

    <div class="dfca-filters">
        <a href="?page=dfca-reports"                       class="<?php echo $status_filter === ''            ? 'is-active' : ''; ?>">All</a>
        <a href="?page=dfca-reports&status=pending"        class="<?php echo $status_filter === 'pending'     ? 'is-active' : ''; ?>">Pending <em>(<?php echo $count_for( 'pending' ); ?>)</em></a>
        <a href="?page=dfca-reports&status=abandoned"      class="<?php echo $status_filter === 'abandoned'   ? 'is-active' : ''; ?>">Abandoned <em>(<?php echo $count_for( 'abandoned' ); ?>)</em></a>
        <a href="?page=dfca-reports&status=recovered"      class="<?php echo $status_filter === 'recovered'   ? 'is-active' : ''; ?>">Recovered <em>(<?php echo $count_for( 'recovered' ); ?>)</em></a>
        <a href="?page=dfca-reports&status=lost"           class="<?php echo $status_filter === 'lost'        ? 'is-active' : ''; ?>">Lost <em>(<?php echo $count_for( 'lost' ); ?>)</em></a>
        <a href="?page=dfca-reports&status=unsubscribed"   class="<?php echo $status_filter === 'unsubscribed' ? 'is-active' : ''; ?>">Unsubscribed <em>(<?php echo $count_for( 'unsubscribed' ); ?>)</em></a>
    </div>

    <div class="dfca-table-wrap">
        <table class="dfca-table">
            <thead><tr>
                <th>Customer</th><th>Email</th><th>Cart Total</th><th>Status</th><th>Order</th><th>Created</th><th>Updated</th>
            </tr></thead>
            <tbody>
            <?php if ( $rows ): foreach ( $rows as $r ): ?>
                <tr>
                    <td><?php echo esc_html( $r->user_name ?: '—' ); ?></td>
                    <td><?php echo esc_html( $r->user_email ); ?></td>
                    <td><?php echo $cur . number_format( $r->cart_total, 2, ',', ' ' ); ?></td>
                    <td><span class="dfca-status dfca-status-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( ucfirst( $r->status ) ); ?></span></td>
                    <td><?php if ( $r->order_id ): ?><a href="<?php echo admin_url( 'post.php?post=' . (int) $r->order_id . '&action=edit' ); ?>">#<?php echo (int) $r->order_id; ?></a><?php else: ?>—<?php endif; ?></td>
                    <td><?php echo esc_html( date_i18n( 'M j, Y g:i a', strtotime( $r->created_at ) ) ); ?></td>
                    <td><?php echo esc_html( date_i18n( 'M j, Y g:i a', strtotime( $r->updated_at ) ) ); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="7" class="dfca-empty">No carts match this filter.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ( $pages > 1 ): ?>
    <div class="dfca-pagination">
        <?php for ( $i = 1; $i <= $pages; $i++ ): ?>
            <a href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>" class="<?php echo $i === $paged ? 'is-active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</section>
