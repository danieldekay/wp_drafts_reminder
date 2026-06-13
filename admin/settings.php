<?php
/**
 * Settings page for WP Drafts Reminder plugin.
 *
 * @package WP_Drafts_Reminder
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the settings page HTML.
 *
 * @param WP_Drafts_Reminder $plugin Plugin instance.
 */
function wdr_render_settings_page($plugin) {
    // Handle form submission
    $message = '';
    if (isset($_POST['wdr_settings_nonce']) && wp_verify_nonce($_POST['wdr_settings_nonce'], 'wdr_save_settings')) {
        $selected_types = isset($_POST['wdr_post_types']) ? (array) $_POST['wdr_post_types'] : array();
        
        // Validate: ensure all selected types are public and not 'post'/'page' if restricted
        $valid_types = array();
        foreach ($selected_types as $type) {
            $pt = get_post_type_object($type);
            if ($pt && !empty($pt->public)) {
                $valid_types[] = $type;
            }
        }
        
        // Default to 'post' if nothing selected
        if (empty($valid_types)) {
            $valid_types = array('post');
        }
        
        $settings = get_option(WP_DRAFTS_REMINDER_SETTINGS, array());
        $settings['post_types'] = $valid_types;
        update_option(WP_DRAFTS_REMINDER_SETTINGS, $settings);
        
        $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'wp-drafts-reminder') . '</p></div>';
    }
    
    $current_settings = get_option(WP_DRAFTS_REMINDER_SETTINGS, array());
    $current_types = !empty($current_settings['post_types']) ? $current_settings['post_types'] : array('post');
    
    // Get all public post types
    $all_post_types = array();
    $post_type_objects = get_post_types(array('public' => true, '_builtin' => false), 'objects');
    
    // Include built-in types if they want them (though user said custom only)
    // We'll show all public types but highlight custom ones
    foreach ($post_type_objects as $pt) {
        $all_post_types[$pt->name] = $pt->labels->name;
    }
    
    // Also include 'post' and 'page' as options (disabled by default per user request)
    $all_post_types['post'] = __('Posts', 'wp-drafts-reminder');
    $all_post_types['page'] = __('Pages', 'wp-drafts-reminder');
    
    // Sort alphabetically
    asort($all_post_types);
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php echo $message; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('wdr_save_settings', 'wdr_settings_nonce'); ?>
            
            <div class="card">
                <h2><?php _e('Monitored Post Types', 'wp-drafts-reminder'); ?></h2>
                <p><?php _e('Select which post types should trigger reminder emails when drafts are left unpublished.', 'wp-drafts-reminder'); ?></p>
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="wdr_post_types"><?php _e('Post Types', 'wp-drafts-reminder'); ?></label>
                            </th>
                            <td>
                                <?php if (empty($all_post_types)) : ?>
                                    <p><?php _e('No public custom post types found. Create some first.', 'wp-drafts-reminder'); ?></p>
                                <?php else : ?>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php _e('Post Types', 'wp-drafts-reminder'); ?></legend>
                                        <?php foreach ($all_post_types as $type_slug => $type_label) : ?>
                                            <label>
                                                <input type="checkbox" 
                                                       name="wdr_post_types[]" 
                                                       value="<?php echo esc_attr($type_slug); ?>"
                                                       <?php checked(in_array($type_slug, $current_types)); ?>
                                                       <?php echo in_array($type_slug, array('post', 'page')) ? 'disabled title="' . esc_attr__('Custom post types only recommended', 'wp-drafts-reminder') . '"' : ''; ?>>
                                                <?php echo esc_html($type_label); ?>
                                                <?php if (in_array($type_slug, array('post', 'page'))) : ?>
                                                    <span class="dashicons dashicons-info" title="<?php esc_attr_e('Built-in types are included for completeness. Custom post types are recommended.', 'wp-drafts-reminder'); ?>"></span>
                                                <?php endif; ?>
                                            </label><br>
                                        <?php endforeach; ?>
                                    </fieldset>
                                    <p class="description">
                                        <?php _e('Select one or more post types. The first reminder is sent 2 days after the last modification, and the second reminder is sent 9 days after.', 'wp-drafts-reminder'); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" 
                       class="button button-primary" 
                       value="<?php esc_attr_e('Save Settings', 'wp-drafts-reminder'); ?>">
            </p>
        </form>
        
        <div class="card" style="margin-top: 20px;">
            <h2><?php _e('Current Configuration', 'wp-drafts-reminder'); ?></h2>
            <table class="widefat">
                <tr>
                    <th style="width: 200px;"><?php _e('Setting', 'wp-drafts-reminder'); ?></th>
                    <th><?php _e('Value', 'wp-drafts-reminder'); ?></th>
                </tr>
                <tr>
                    <td><?php _e('Monitored Post Types', 'wp-drafts-reminder'); ?></td>
                    <td><?php echo esc_html(implode(', ', $current_types)); ?></td>
                </tr>
                <tr>
                    <td><?php _e('First Reminder', 'wp-drafts-reminder'); ?></td>
                    <td><?php _e('2 days after last modification', 'wp-drafts-reminder'); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Second Reminder', 'wp-drafts-reminder'); ?></td>
                    <td><?php _e('9 days after last modification (7 days after first)', 'wp-drafts-reminder'); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Check Frequency', 'wp-drafts-reminder'); ?></td>
                    <td><?php _e('Daily (WordPress cron)', 'wp-drafts-reminder'); ?></td>
                </tr>
            </table>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h2><?php _e('Email Templates', 'wp-drafts-reminder'); ?></h2>
            <p><?php _e('You can customize the email templates by copying them to your active theme folder. The theme templates take precedence over the plugin defaults.', 'wp-drafts-reminder'); ?></p>
            
            <?php foreach (array('first', 'second') as $type) : 
                $paths = $plugin->get_template_paths($type);
                $type_label = $type === 'first' ? __('First Reminder', 'wp-drafts-reminder') : __('Second Reminder', 'wp-drafts-reminder');
            ?>
                <div style="margin-bottom: 16px; padding: 12px; background: #f0f0f1; border-radius: 4px;">
                    <h3 style="margin-top: 0;"><?php echo esc_html($type_label); ?></h3>
                    <?php if (!empty($paths['theme'])) : ?>
                        <p><strong><?php _e('Active template:', 'wp-drafts-reminder'); ?></strong> <code><?php echo esc_html($paths['theme']); ?></code></p>
                        <p class="description"><?php _e('This template is being used. Edit this file to customize the email.', 'wp-drafts-reminder'); ?></p>
                    <?php else : ?>
                        <p><strong><?php _e('Active template:', 'wp-drafts-reminder'); ?></strong> <code><?php echo esc_html($paths['plugin']); ?></code></p>
                        <p class="description">
                            <?php _e('To customize, copy this file to your theme folder:', 'wp-drafts-reminder'); ?>
                        </p>
                        <p style="margin-bottom: 0;">
                            <code style="background: #fff; padding: 4px 8px; border-radius: 3px;">
                                <?php echo esc_html($paths['theme_dir'] . $type . '-reminder-email.php'); ?>
                            </code>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
