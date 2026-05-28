<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$is_pro   = dfca_is_premium();
$channel  = sanitize_key( $_GET['channel'] ?? 'email' );
$edit_id  = (int) ( $_GET['edit'] ?? 0 );
$is_new   = isset( $_GET['new'] );
$tpl_mgr  = DFCA_Templates::instance();
$editing  = $edit_id ? $tpl_mgr->get( $edit_id ) : null;

if ( $is_new || $editing ):
    /* ============ EDIT / NEW FORM ============ */
    $row = $editing ?: (object) [
        'id'=>0,'name'=>'','channel'=>$channel,'trigger_value'=>1,'trigger_unit'=>'hours',
        'subject'=>'','body'=>'','is_active'=>1,'is_premium'=>0,
    ];
    $is_premium_channel = in_array( $row->channel, [ 'sms', 'whatsapp' ], true );
?>
<section class="dfca-section">
    <div class="dfca-section-head">
        <h2><?php echo $editing ? 'Edit Template' : 'Create New Template'; ?></h2>
        <div class="dfca-section-actions">
            <?php if ( $editing ): ?>
                <button type="button" class="dfca-btn dfca-btn-light dfca-preview-btn" data-id="<?php echo (int) $row->id; ?>">👁️ Preview</button>
                <?php if ( $editing && $row->channel === 'email' ): ?>
                    <button type="button" class="dfca-btn dfca-btn-success" onclick="document.getElementById('dfca-test-form').style.display=document.getElementById('dfca-test-form').style.display==='none'?'':'none'">✉️ Send Test</button>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?php echo admin_url( 'admin.php?page=dfca-templates' ); ?>" class="dfca-btn dfca-btn-light">← Back</a>
        </div>
    </div>

    <?php if ( $editing && $row->channel === 'email' ): ?>
    <div id="dfca-test-form" style="display:none;background:#e7f0fd;border:1px solid #b6cef5;border-radius:10px;padding:18px 22px;margin-bottom:16px;">
        <strong style="color:var(--lm-blue);">✉️ Send Test Email</strong>
        <p style="margin:6px 0 12px;font-size:0.86em;color:var(--lm-muted);">Sends a test with sample cart data. Submits as a normal form — same as all other plugin actions.</p>
        <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <?php wp_nonce_field( 'dfca_action' ); ?>
            <input type="hidden" name="dfca_action" value="send_test_email">
            <input type="hidden" name="template_id" value="<?php echo (int) $row->id; ?>">
            <div style="flex:1;min-width:200px;">
                <label style="font-size:0.84em;font-weight:600;display:block;margin-bottom:4px;">Send to</label>
                <input type="email" name="send_to" value="<?php echo esc_attr( get_option('admin_email') ); ?>" style="width:100%;padding:9px 13px;border:1px solid var(--lm-border);border-radius:8px;font-size:0.9em;">
            </div>
            <button type="submit" class="dfca-btn dfca-btn-success">📤 Send Now</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ( $is_premium_channel && ! $is_pro ): ?>
    <div class="dfca-flash dfca-flash-error">⭐ <?php echo esc_html( ucfirst( $row->channel ) ); ?> templates require a Premium license. <a href="<?php echo admin_url( 'admin.php?page=dfca-license' ); ?>">Activate yours →</a></div>
    <?php endif; ?>

    <div class="dfca-card">
        <form method="post" class="dfca-form">
            <?php wp_nonce_field( 'dfca_action' ); ?>
            <input type="hidden" name="dfca_action" value="save_template">
            <input type="hidden" name="template_id" value="<?php echo (int) $row->id; ?>">

            <div class="dfca-grid2">
                <div class="dfca-field">
                    <label>Template Name</label>
                    <input type="text" name="name" value="<?php echo esc_attr( $row->name ); ?>" required>
                </div>
                <div class="dfca-field">
                    <label>Channel</label>
                    <select name="channel" id="dfca-channel-select" <?php echo $is_pro ? '' : 'disabled'; ?>>
                        <option value="email"    <?php selected( $row->channel, 'email' ); ?>>Email</option>
                        <option value="sms"      <?php selected( $row->channel, 'sms' ); ?> <?php echo $is_pro ? '' : 'disabled'; ?>>SMS (Premium)</option>
                        <option value="whatsapp" <?php selected( $row->channel, 'whatsapp' ); ?> <?php echo $is_pro ? '' : 'disabled'; ?>>WhatsApp (Premium)</option>
                    </select>
                    <?php if ( ! $is_pro ): ?><input type="hidden" name="channel" value="email"><?php endif; ?>
                </div>
                <div class="dfca-field">
                    <label>Trigger — send after</label>
                    <div style="display:flex;gap:8px;">
                        <input type="number" name="trigger_value" min="1" value="<?php echo (int) $row->trigger_value; ?>" style="width:90px">
                        <select name="trigger_unit" style="flex:1;">
                            <option value="minutes" <?php selected( $row->trigger_unit, 'minutes' ); ?>>Minutes</option>
                            <option value="hours"   <?php selected( $row->trigger_unit, 'hours' ); ?>>Hours</option>
                            <option value="days"    <?php selected( $row->trigger_unit, 'days' ); ?>>Days</option>
                        </select>
                    </div>
                    <p class="dfca-help">Time after cart is marked abandoned.</p>
                </div>
                <div class="dfca-field">
                    <label>Active</label>
                    <label class="dfca-switch">
                        <input type="checkbox" name="is_active" value="1" <?php checked( $row->is_active ); ?>>
                        <span class="dfca-switch-slider"></span>
                    </label>
                    <p class="dfca-help">When on, this template will be sent automatically.</p>
                </div>
            </div>

            <div class="dfca-field" id="dfca-subject-field" <?php echo $row->channel !== 'email' ? 'style="display:none"' : ''; ?>>
                <label>Subject</label>
                <input type="text" name="subject" value="<?php echo esc_attr( $row->subject ); ?>" placeholder="You left something behind at {site_name}">
            </div>

            <div class="dfca-field">
                <label>Message Body</label>
                <?php
                wp_editor( $row->body, 'dfca_body', [
                    'textarea_name' => 'body',
                    'textarea_rows' => 14,
                    'media_buttons' => $is_pro,
                    'teeny'         => ! $is_pro,
                ]);
                ?>
            </div>

            <div class="dfca-merge-tags">
                <strong>Merge tags (click to copy):</strong>
                <?php foreach ( [ '{customer_name}','{customer_email}','{cart_items}','{cart_total}','{recovery_url}','{coupon_code}','{site_name}','{site_url}','{unsubscribe_url}' ] as $tag ): ?>
                    <code onclick="navigator.clipboard.writeText('<?php echo esc_js( $tag ); ?>');this.style.background='var(--lm-green)';this.style.color='#fff';setTimeout(()=>{this.style.background='';this.style.color='';},800);"><?php echo esc_html( $tag ); ?></code>
                <?php endforeach; ?>
            </div>

            <div class="dfca-form-actions">
                <button type="submit" class="dfca-btn dfca-btn-primary">💾 Save Template</button>
                <a href="<?php echo admin_url( 'admin.php?page=dfca-templates' ); ?>" class="dfca-btn dfca-btn-light">Cancel</a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('change', function(e){
    if (e.target && e.target.id === 'dfca-channel-select') {
        document.getElementById('dfca-subject-field').style.display =
            e.target.value === 'email' ? '' : 'none';
    }
});
</script>

<?php else:
    /* ============ LIST ============ */
    $rows = $tpl_mgr->all( $channel );
?>
<section class="dfca-section">
    <div class="dfca-section-head">
        <h2>Follow Up Templates</h2>
        <div class="dfca-section-actions">
            <form method="post" style="display:inline">
                <?php wp_nonce_field( 'dfca_action' ); ?>
                <input type="hidden" name="dfca_action" value="restore_defaults">
                <button class="dfca-btn dfca-btn-light" onclick="return confirm('Replace all templates with defaults? This cannot be undone.')">Restore Defaults</button>
            </form>
            <a href="<?php echo admin_url( 'admin.php?page=dfca-templates&new=1&channel=' . $channel ); ?>" class="dfca-btn dfca-btn-primary">+ Create New Template</a>
        </div>
    </div>

    <div class="dfca-channel-tabs">
        <a href="?page=dfca-templates&channel=email"    class="<?php echo $channel === 'email'    ? 'is-active' : ''; ?>">Email</a>
        <a href="?page=dfca-templates&channel=sms"      class="<?php echo $channel === 'sms'      ? 'is-active' : ''; ?>">SMS <?php if ( ! $is_pro ) echo '<span class="dfca-mini-pro">PRO</span>'; ?></a>
        <a href="?page=dfca-templates&channel=whatsapp" class="<?php echo $channel === 'whatsapp' ? 'is-active' : ''; ?>">WhatsApp <?php if ( ! $is_pro ) echo '<span class="dfca-mini-pro">PRO</span>'; ?></a>
    </div>

    <?php if ( ! $is_pro && $channel !== 'email' ): ?>
    <div class="dfca-flash dfca-flash-info">
        ⭐ <strong><?php echo esc_html( ucfirst( $channel ) ); ?> templates are a Premium feature.</strong>
        <a href="<?php echo admin_url( 'admin.php?page=dfca-license' ); ?>">Activate your license →</a> to unlock multi-channel recovery campaigns.
    </div>
    <?php endif; ?>

    <div class="dfca-table-wrap">
        <table class="dfca-table">
            <thead><tr>
                <th>Template Name</th>
                <th>Trigger After</th>
                <th>Sent <?php if ( ! $is_pro ) echo '<span class="dfca-mini-pro">PRO</span>'; ?></th>
                <th>Open Rate <?php if ( ! $is_pro ) echo '<span class="dfca-mini-pro">PRO</span>'; ?></th>
                <th>Click Rate <?php if ( ! $is_pro ) echo '<span class="dfca-mini-pro">PRO</span>'; ?></th>
                <th>Conv. Rate <?php if ( ! $is_pro ) echo '<span class="dfca-mini-pro">PRO</span>'; ?></th>
                <th>Status</th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
            <?php if ( $rows ): foreach ( $rows as $r ):
                $stats = $tpl_mgr->stats( $r->id );
                $locked_row = ( ! $is_pro && ( $r->is_premium || $r->channel !== 'email' ) );
            ?>
                <tr class="<?php echo $locked_row ? 'is-locked' : ''; ?>">
                    <td>
                        <strong><?php echo esc_html( $r->name ); ?></strong>
                        <?php if ( $r->is_premium ): ?><span class="dfca-mini-pro">PRO</span><?php endif; ?>
                    </td>
                    <td><?php echo (int) $r->trigger_value . ' ' . esc_html( $r->trigger_unit ); ?></td>
                    <td><?php echo $is_pro ? (int) $stats['sent'] : '<span class="dfca-blur">' . (int) $stats['sent'] . '</span>'; ?></td>
                    <td><?php echo $is_pro ? $stats['open_rate'] . '%' : '<span class="dfca-blur">--%</span>'; ?></td>
                    <td><?php echo $is_pro ? $stats['click_rate'] . '%' : '<span class="dfca-blur">--%</span>'; ?></td>
                    <td><?php echo $is_pro ? $stats['conv_rate'] . '%' : '<span class="dfca-blur">--%</span>'; ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <?php wp_nonce_field( 'dfca_action' ); ?>
                            <input type="hidden" name="dfca_action" value="toggle_template">
                            <input type="hidden" name="template_id" value="<?php echo (int) $r->id; ?>">
                            <button class="dfca-switch-btn <?php echo $r->is_active ? 'is-on' : ''; ?>" type="submit" aria-label="Toggle"></button>
                        </form>
                    </td>
                    <td class="dfca-row-actions">
                        <a href="?page=dfca-templates&edit=<?php echo (int) $r->id; ?>" title="Edit">✏️</a>
                        <button title="Preview"  type="button" class="dfca-preview-btn" data-id="<?php echo (int) $r->id; ?>">👁️</button>
                        <?php if ( $r->channel === 'email' ): ?>
                        <button title="Send Test" type="button" onclick="dfcaOpenTest(<?php echo (int) $r->id; ?>)">✉️</button>
                        <?php endif; ?>
                        <form method="post" style="display:inline">
                            <?php wp_nonce_field( 'dfca_action' ); ?>
                            <input type="hidden" name="dfca_action" value="duplicate_template">
                            <input type="hidden" name="template_id" value="<?php echo (int) $r->id; ?>">
                            <button title="Duplicate" type="submit">📋</button>
                        </form>
                        <form method="post" style="display:inline">
                            <?php wp_nonce_field( 'dfca_action' ); ?>
                            <input type="hidden" name="dfca_action" value="delete_template">
                            <input type="hidden" name="template_id" value="<?php echo (int) $r->id; ?>">
                            <button title="Delete" type="submit" onclick="return confirm('Delete this template?')">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" class="dfca-empty">No templates yet for this channel.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ( ! $is_pro ): ?>
    <div class="dfca-upsell">
        <h3>⭐ Unlock Premium Templates</h3>
        <p>Free tier includes 1 active email template. <strong>Premium unlocks unlimited email templates, plus SMS and WhatsApp recovery campaigns</strong> with multi-step sequences and full analytics.</p>
        <a href="<?php echo admin_url( 'admin.php?page=dfca-license' ); ?>" class="dfca-btn dfca-btn-warning">Activate Premium License →</a>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ( ! $is_new && ! $editing ): ?>
<div id="dfca-inline-test-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:100000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:460px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="background:linear-gradient(135deg,var(--lm-blue),var(--lm-dark));padding:18px 24px;color:#fff;display:flex;justify-content:space-between;align-items:center;">
            <strong>✉️ Send Test Email</strong>
            <button onclick="document.getElementById('dfca-inline-test-modal').style.display='none'" style="background:rgba(255,255,255,0.2);border:0;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:16px;">×</button>
        </div>
        <div style="padding:22px 24px;">
            <p style="margin:0 0 14px;font-size:0.88em;color:var(--lm-muted);">Sends a test with sample cart data. Submits as a normal form — result shown as a message when the page reloads.</p>
            <form method="post">
                <?php wp_nonce_field( 'dfca_action' ); ?>
                <input type="hidden" name="dfca_action" value="send_test_email">
                <input type="hidden" name="template_id" id="dfca-test-tpl-id" value="">
                <div style="margin-bottom:14px;">
                    <label style="font-size:0.88em;font-weight:600;display:block;margin-bottom:4px;">Recipient Email</label>
                    <input type="email" name="send_to" value="<?php echo esc_attr( get_option('admin_email') ); ?>" style="width:100%;padding:10px 14px;border:1px solid var(--lm-border);border-radius:8px;font-size:0.9em;">
                </div>
                <div style="display:flex;justify-content:flex-end;gap:10px;">
                    <button type="button" onclick="document.getElementById('dfca-inline-test-modal').style.display='none'" class="dfca-btn dfca-btn-light">Cancel</button>
                    <button type="submit" class="dfca-btn dfca-btn-success">📤 Send Test</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function dfcaOpenTest(id){
    document.getElementById('dfca-test-tpl-id').value = id;
    document.getElementById('dfca-inline-test-modal').style.display = 'flex';
}
</script>
<?php endif; ?>
