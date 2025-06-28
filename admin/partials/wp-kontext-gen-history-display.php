<?php
/**
 * History page display
 */

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

global $wpdb;
$table_name = $wpdb->prefix . 'kontext_gen_history';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    check_admin_referer('wp_kontext_gen_delete_' . $_GET['id']);
    $wpdb->delete($table_name, array('id' => intval($_GET['id'])));
    echo '<div class="notice notice-success"><p>' . __('Generation deleted successfully.', 'wp-kontext-gen') . '</p></div>';
}

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get total count
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$total_pages = ceil($total_items / $per_page);

// Get history items
$history_items = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

// Get total cost for current user
$admin = new WP_Kontext_Gen_Admin('wp-kontext-gen', WP_KONTEXT_GEN_VERSION);
$total_cost = $admin->get_total_cost();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if ($total_cost > 0) : ?>
        <div class="notice notice-info">
            <p><strong><?php _e('Total API Cost:', 'wp-kontext-gen'); ?></strong> $<?php echo number_format($total_cost, 4); ?> USD</p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($history_items)) : ?>
        <div class="tablenav top">
            <div class="alignright">
                <button type="button" class="button" id="clear_history_btn"><?php _e('Clear All History', 'wp-kontext-gen'); ?></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($history_items)) : ?>
        <p><?php _e('No generations yet.', 'wp-kontext-gen'); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 150px;"><?php _e('Preview', 'wp-kontext-gen'); ?></th>
                    <th><?php _e('Prompt', 'wp-kontext-gen'); ?></th>
                    <th style="width: 100px;"><?php _e('Status', 'wp-kontext-gen'); ?></th>
                    <th style="width: 80px;"><?php _e('Cost', 'wp-kontext-gen'); ?></th>
                    <th style="width: 150px;"><?php _e('Date', 'wp-kontext-gen'); ?></th>
                    <th style="width: 140px;"><?php _e('Actions', 'wp-kontext-gen'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history_items as $item) : 
                    $parameters = json_decode($item->parameters, true);
                ?>
                    <tr>
                        <td>
                            <?php if ($item->output_image_url) : ?>
                                <a href="<?php echo esc_url($item->output_image_url); ?>" target="_blank">
                                    <img src="<?php echo esc_url($item->output_image_url); ?>" style="max-width: 150px; height: auto;" />
                                </a>
                            <?php elseif ($item->input_image_url) : ?>
                                <img src="<?php echo esc_url($item->input_image_url); ?>" style="max-width: 150px; height: auto; opacity: 0.5;" />
                            <?php else : ?>
                                <div style="width: 150px; height: 100px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                    <?php _e('No image', 'wp-kontext-gen'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html(substr($item->prompt, 0, 100)); ?><?php echo strlen($item->prompt) > 100 ? '...' : ''; ?></strong>
                            <?php if (!empty($parameters)) : ?>
                                <div style="margin-top: 5px; font-size: 12px; color: #666;">
                                    <?php 
                                    $params_display = array();
                                    if (isset($parameters['aspect_ratio'])) $params_display[] = 'Aspect: ' . $parameters['aspect_ratio'];
                                    if (isset($parameters['num_inference_steps'])) $params_display[] = 'Steps: ' . $parameters['num_inference_steps'];
                                    if (isset($parameters['guidance'])) $params_display[] = 'Guidance: ' . $parameters['guidance'];
                                    if (isset($parameters['go_fast']) && $parameters['go_fast']) $params_display[] = 'Fast mode';
                                    echo implode(' | ', $params_display);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch ($item->status) {
                                case 'succeeded':
                                    $status_class = 'success';
                                    $status_text = __('Completed', 'wp-kontext-gen');
                                    break;
                                case 'failed':
                                    $status_class = 'error';
                                    $status_text = __('Failed', 'wp-kontext-gen');
                                    break;
                                case 'processing':
                                    $status_class = 'warning';
                                    $status_text = __('Processing', 'wp-kontext-gen');
                                    break;
                                case 'starting':
                                    $status_class = 'warning';
                                    $status_text = __('Starting', 'wp-kontext-gen');
                                    break;
                                default:
                                    $status_class = 'warning';
                                    $status_text = ucfirst($item->status);
                                    break;
                            }
                            ?>
                            <span class="status-badge status-<?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($item->cost_usd > 0) : ?>
                                <span class="cost-display">$<?php echo number_format($item->cost_usd, 4); ?></span>
                            <?php else : ?>
                                <span class="cost-display">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at)); ?>
                        </td>
                        <td>
                            <?php if ($item->output_image_url) : ?>
                                <a href="<?php echo esc_url($item->output_image_url); ?>" class="button button-small" target="_blank">
                                    <?php _e('View', 'wp-kontext-gen'); ?>
                                </a>
                                <?php if (!$item->attachment_id) : ?>
                                    <button type="button" class="button button-small save-to-media-btn" 
                                            data-url="<?php echo esc_attr($item->output_image_url); ?>" 
                                            data-title="<?php echo esc_attr(substr($item->prompt, 0, 50) . '...'); ?>">
                                        <?php _e('Save to Media', 'wp-kontext-gen'); ?>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($item->attachment_id) : ?>
                                <a href="<?php echo admin_url('post.php?post=' . $item->attachment_id . '&action=edit'); ?>" class="button button-small">
                                    <?php _e('Edit in WP', 'wp-kontext-gen'); ?>
                                </a>
                            <?php endif; ?>
                            <button type="button" class="button button-small delete-history-item" data-id="<?php echo $item->id; ?>">
                                <?php _e('Delete', 'wp-kontext-gen'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}
.status-success {
    background: #d4edda;
    color: #155724;
}
.status-error {
    background: #f8d7da;
    color: #721c24;
}
.status-warning {
    background: #fff3cd;
    color: #856404;
}
</style>