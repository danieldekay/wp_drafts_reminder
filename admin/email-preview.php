<?php
/**
 * Email preview page for WP Drafts Reminder plugin.
 *
 * @package WP_Drafts_Reminder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the email preview page HTML.
 *
 * @param WP_Drafts_Reminder $plugin Plugin instance.
 */
function wdr_render_email_preview($plugin) {
    $reminder_type = isset($_GET['reminder_type']) ? sanitize_text_field($_GET['reminder_type']) : 'first';
    $preview_drafts = array();
    $subject = '';
    $html_content = '';
    $error = '';
    $success_message = '';
    
    // Check for test send success
    if (isset($_GET['message']) && $_GET['message'] === 'test-sent') {
        $success_message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Test email sent successfully to your admin address.', 'wp-drafts-reminder') . '</p></div>';
    }
    
    // Generate sample data for preview
    $sample_drafts = $plugin->get_sample_drafts_for_preview($reminder_type);
    
    if (!empty($sample_drafts)) {
        $subject = $plugin->get_reminder_subject($reminder_type);
        $html_content = $plugin->load_email_template_for_preview($sample_drafts, $reminder_type);
    } else {
        $error = __('No sample drafts available for preview. Create some draft posts in the selected post types first.', 'wp-drafts-reminder');
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php echo $success_message; ?>
            
        <div class="card">
            <h2><?php _e('Email Preview', 'wp-drafts-reminder'); ?></h2>
            <p><?php _e('Preview how reminder emails will look before they are sent. This uses sample data to demonstrate the template rendering.', 'wp-drafts-reminder'); ?></p>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="wp-drafts-reminder-preview">
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="reminder_type"><?php _e('Reminder Type', 'wp-drafts-reminder'); ?></label>
                            </th>
                            <td>
                                <select name="reminder_type" id="reminder_type">
                                    <option value="first" <?php selected($reminder_type, 'first'); ?>>
                                        <?php _e('First Reminder (2 days)', 'wp-drafts-reminder'); ?>
                                    </option>
                                    <option value="second" <?php selected($reminder_type, 'second'); ?>>
                                        <?php _e('Second Reminder (9 days)', 'wp-drafts-reminder'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Select which reminder template to preview.', 'wp-drafts-reminder'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Preview Email', 'wp-drafts-reminder'); ?>">
                </p>
            </form>
        </div>
        
        <?php if (!empty($error)) : ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error); ?></p>
            </div>
        <?php else : ?>
            <div class="card">
                <h2>
                    <?php echo esc_html($reminder_type === 'first' ? __('First Reminder Preview', 'wp-drafts-reminder') : __('Second Reminder Preview', 'wp-drafts-reminder')); ?>
                </h2>
                
                <div style="background: #f0f0f1; padding: 16px; margin-bottom: 16px; border-radius: 4px;">
                    <strong><?php _e('Subject:', 'wp-drafts-reminder'); ?></strong> 
                    <?php echo esc_html($subject); ?>
                </div>
                
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 0; overflow: hidden;">
                    <?php echo $html_content; ?>
                </div>
                
                <p class="description" style="margin-top: 16px;">
                    <?php _e('This preview shows how the email will appear to recipients. The actual email uses real draft data from your site.', 'wp-drafts-reminder'); ?>
                </p>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Test Send', 'wp-drafts-reminder'); ?></h2>
                <p><?php _e('Send a real test email to your administrator address using current draft data.', 'wp-drafts-reminder'); ?></p>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('wp_drafts_reminder_test', 'wp_drafts_reminder_nonce'); ?>
                    <input type="hidden" name="action" value="wp_drafts_reminder_test">
                    <input type="hidden" name="wdr_test_type" value="<?php echo esc_attr($reminder_type); ?>">
                    
                    <p>
                        <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Send Test Email to Admin', 'wp-drafts-reminder'); ?>">
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
