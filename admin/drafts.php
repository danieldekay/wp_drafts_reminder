<?php
/**
 * Drafts management page for WP Drafts Reminder plugin.
 *
 * @package WP_Drafts_Reminder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle draft deletion via admin post action.
 *
 * @param WP_Drafts_Reminder $plugin Plugin instance.
 */
function wdr_handle_delete_draft($plugin) {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-drafts-reminder'));
    }

    if (!isset($_POST['wdr_delete_nonce']) || !wp_verify_nonce($_POST['wdr_delete_nonce'], 'wdr_delete_draft')) {
        wp_die(__('Security check failed.', 'wp-drafts-reminder'));
    }

    $draft_id = isset($_POST['wdr_draft_id']) ? absint($_POST['wdr_draft_id']) : 0;
    if (!$draft_id) {
        wp_die(__('Invalid draft ID.', 'wp-drafts-reminder'));
    }

    // Verify the draft belongs to a monitored post type
    $post = get_post($draft_id);
    if (!$post || $post->post_status !== 'draft') {
        wp_die(__('Draft not found or not in draft status.', 'wp-drafts-reminder'));
    }

    $monitored_types = $plugin->get_monitored_post_types();
    if (!in_array($post->post_type, $monitored_types, true)) {
        wp_die(__('This post type is not monitored by the reminder system.', 'wp-drafts-reminder'));
    }

    // Remove any reminder meta to allow re-reminding if draft is edited
    delete_post_meta($draft_id, WP_DRAFTS_REMINDER_FIRST_REMINDER_META);
    delete_post_meta($draft_id, WP_DRAFTS_REMINDER_SECOND_REMINDER_META);

    // Redirect back with success message
    $redirect = add_query_arg('wdr_message', 'draft-deleted', wp_get_referer());
    wp_redirect($redirect);
    exit;
}

/**
 * Render the drafts management page HTML.
 *
 * @param WP_Drafts_Reminder $plugin Plugin instance.
 */
function wdr_render_drafts_page($plugin) {
    // Get all drafts for monitored post types
    $all_drafts = $plugin->get_all_draft_posts();
    
    // Get drafts needing first and second reminders
    $first_pending = $plugin->get_drafts_needing_first_reminder();
    $second_pending = $plugin->get_drafts_needing_second_reminder();
    
    // Build lookup arrays for quick status checking
    $first_sent_ids = wp_list_pluck($first_pending, 'ID');
    $second_sent_ids = wp_list_pluck($second_pending, 'ID');
    
    // Handle deletion redirect message
    $message = '';
    if (isset($_GET['wdr_message']) && $_GET['wdr_message'] === 'draft-deleted') {
        $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Draft reminder status cleared successfully.', 'wp-drafts-reminder') . '</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php echo $message; ?>
        
        <div class="card" style="margin-bottom: 20px;">
            <h2><?php _e('Draft Status Overview', 'wp-drafts-reminder'); ?></h2>
            <table class="widefat">
                <tr>
                    <th><?php _e('Status', 'wp-drafts-reminder'); ?></th>
                    <th><?php _e('Count', 'wp-drafts-reminder'); ?></th>
                </tr>
                <tr>
                    <td><?php _e('Total Drafts', 'wp-drafts-reminder'); ?></td>
                    <td><?php echo count($all_drafts); ?></td>
                </tr>
                <tr>
                    <td><?php _e('First Reminder Pending', 'wp-drafts-reminder'); ?></td>
                    <td><?php echo count($first_pending); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Second Reminder Pending', 'wp-drafts-reminder'); ?></td>
                    <td><?php echo count($second_pending); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2><?php _e('All Drafts', 'wp-drafts-reminder'); ?></h2>
            
            <?php if (empty($all_drafts)) : ?>
                <p><?php _e('No drafts found for the monitored post types.', 'wp-drafts-reminder'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'wp-drafts-reminder'); ?></th>
                            <th><?php _e('Post Type', 'wp-drafts-reminder'); ?></th>
                            <th><?php _e('Author', 'wp-drafts-reminder'); ?></th>
                            <th><?php _e('Last Modified', 'wp-drafts-reminder'); ?></th>
                            <th><?php _e('Scheduled Reminder', 'wp-drafts-reminder'); ?></th>
                            <th><?php _e('First Reminder', 'wp-drafts-reminder'); ?></th>
                            <th><?php _e('Second Reminder', 'wp-drafts-reminder'); ?></th>
                            <th><?php _e('Actions', 'wp-drafts-reminder'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_drafts as $draft) : 
                            $author = get_userdata($draft->post_author);
                            $days_old = $plugin->get_days_since_modified($draft->post_modified);
                            $first_sent = get_post_meta($draft->ID, WP_DRAFTS_REMINDER_FIRST_REMINDER_META, true);
                            $second_sent = get_post_meta($draft->ID, WP_DRAFTS_REMINDER_SECOND_REMINDER_META, true);
                            
                            // Determine scheduled reminder date
                            $scheduled_for = '';
                            $modified_time = strtotime($draft->post_modified);
                            
                            if (empty($first_sent)) {
                                // First reminder scheduled for 2 days after modification
                                $scheduled_for = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), 
                                    strtotime('+2 days', $modified_time));
                            } elseif (empty($second_sent)) {
                                // Second reminder scheduled for 9 days after modification
                                $scheduled_for = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), 
                                    strtotime('+9 days', $modified_time));
                            } else {
                                $scheduled_for = __('All sent', 'wp-drafts-reminder');
                            }
                            
                            // Determine status badge
                            $status_class = '';
                            $status_text = '';
                            if (empty($first_sent) && empty($second_sent)) {
                                $status_class = 'status-pending';
                                $status_text = __('Pending', 'wp-drafts-reminder');
                            } elseif (!empty($first_sent) && empty($second_sent)) {
                                $status_class = 'status-sent-first';
                                $status_text = __('First sent', 'wp-drafts-reminder');
                            } elseif (!empty($second_sent)) {
                                $status_class = 'status-complete';
                                $status_text = __('Complete', 'wp-drafts-reminder');
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(get_edit_post_link($draft->ID)); ?>">
                                            <?php echo esc_html($draft->post_title ?: __('(No title)', 'wp-drafts-reminder')); ?>
                                        </a>
                                    </strong>
                                    <?php if (!empty($status_text)) : ?>
                                        <span class="wdr-status-badge <?php echo esc_attr($status_class); ?>">
                                            <?php echo esc_html($status_text); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php 
                                    $post_type_obj = get_post_type_object($draft->post_type);
                                    echo esc_html($post_type_obj ? $post_type_obj->labels->singular_name : $draft->post_type); 
                                ?></td>
                                <td><?php echo esc_html($author ? $author->display_name : __('Unknown', 'wp-drafts-reminder')); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($draft->post_modified))); ?></td>
                                <td>
                                    <?php if (!empty($scheduled_for)) : ?>
                                        <span class="wdr-scheduled-date"><?php echo esc_html($scheduled_for); ?></span>
                                    <?php else : ?>
                                        <span style="color: #646970;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($first_sent)) : ?>
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($first_sent))); ?>
                                    <?php else : ?>
                                        <span style="color: #646970;"><?php _e('Not sent', 'wp-drafts-reminder'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($second_sent)) : ?>
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($second_sent))); ?>
                                    <?php else : ?>
                                        <span style="color: #646970;"><?php _e('Not sent', 'wp-drafts-reminder'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e('Clear reminder status for this draft? This will allow it to be re-reminded.', 'wp-drafts-reminder'); ?>');">
                                        <?php wp_nonce_field('wdr_delete_draft', 'wdr_delete_nonce'); ?>
                                        <input type="hidden" name="action" value="wp_drafts_reminder_delete_draft">
                                        <input type="hidden" name="wdr_draft_id" value="<?php echo esc_attr($draft->ID); ?>">
                                        <input type="submit" class="button button-small button-link-delete" value="<?php esc_attr_e('Clear Status', 'wp-drafts-reminder'); ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
