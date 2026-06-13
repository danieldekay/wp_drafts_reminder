<?php
/**
 * Plugin Name: WP Drafts Reminder
 * Plugin URI: https://github.com/danieldekay/wp_drafts_reminder
 * Description: Sends configurable daily reminder emails for unpublished draft posts, with first and second reminder tiers.
 * Version: 1.1.0
 * Author: Daniel Dekay
 * License: MIT
 * Text Domain: wp-drafts-reminder
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_DRAFTS_REMINDER_VERSION', '1.1.0');
define('WP_DRAFTS_REMINDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DRAFTS_REMINDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_DRAFTS_REMINDER_FIRST_REMINDER_META', '_wdr_first_reminder_sent');
define('WP_DRAFTS_REMINDER_SECOND_REMINDER_META', '_wdr_second_reminder_sent');
define('WP_DRAFTS_REMINDER_SETTINGS', 'wp_drafts_reminder_settings');

/**
 * Main plugin class
 */
class WP_Drafts_Reminder {

    /**
     * Plugin instance
     *
     * @var WP_Drafts_Reminder
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return WP_Drafts_Reminder
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('wp-drafts-reminder', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Schedule cron event if not already scheduled
        if (!wp_next_scheduled('wp_drafts_reminder_check')) {
            wp_schedule_event(time(), 'daily', 'wp_drafts_reminder_check');
        }

        // Hook into the cron event
        add_action('wp_drafts_reminder_check', array($this, 'check_and_send_reminders'));

        // Add admin menu (only for administrators)
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_post_wp_drafts_reminder_test', array($this, 'handle_test_action'));
            add_action('admin_post_wp_drafts_reminder_delete_draft', array($this, 'handle_delete_draft'));
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Schedule the cron event
        if (!wp_next_scheduled('wp_drafts_reminder_check')) {
            wp_schedule_event(time(), 'daily', 'wp_drafts_reminder_check');
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear the scheduled cron event
        wp_clear_scheduled_hook('wp_drafts_reminder_check');
    }

    /**
     * Get the configured post types to monitor.
     *
     * @return array Array of post type slugs.
     */
    public function get_monitored_post_types() {
        $settings = get_option(WP_DRAFTS_REMINDER_SETTINGS, array());
        return !empty($settings['post_types']) ? $settings['post_types'] : array('post');
    }

    /**
     * Check for old drafts and send reminders
     */
    public function check_and_send_reminders() {
        $post_types = $this->get_monitored_post_types();

        // Get drafts needing first reminders (modified > 2 days ago, no first reminder sent)
        $first_reminder_drafts = $this->get_drafts_needing_first_reminder($post_types);

        // Get drafts needing second reminders (modified > 9 days ago, first sent, second not sent)
        $second_reminder_drafts = $this->get_drafts_needing_second_reminder($post_types);

        if (empty($first_reminder_drafts) && empty($second_reminder_drafts)) {
            return;
        }

        // Group first reminder drafts by author
        $first_by_author = $this->group_by_author($first_reminder_drafts);
        foreach ($first_by_author as $author_id => $drafts) {
            $this->send_reminder_email($author_id, $drafts, 'first');
        }

        // Group second reminder drafts by author
        $second_by_author = $this->group_by_author($second_reminder_drafts);
        foreach ($second_by_author as $author_id => $drafts) {
            $this->send_reminder_email($author_id, $drafts, 'second');
        }
    }

    /**
     * Group an array of draft posts by author ID.
     *
     * @param array $drafts Array of post objects.
     * @return array Array keyed by author ID with arrays of posts.
     */
    private function group_by_author($drafts) {
        $drafts_by_author = array();
        foreach ($drafts as $draft) {
            $author_id = $draft->post_author;
            if (!isset($drafts_by_author[$author_id])) {
                $drafts_by_author[$author_id] = array();
            }
            $drafts_by_author[$author_id][] = $draft;
        }
        return $drafts_by_author;
    }

    /**
     * Get draft posts needing a first reminder (modified > 2 days ago, no first reminder sent).
     *
     * @param array $post_types Array of post type slugs to query.
     * @return array Array of post objects.
     */
    private function get_drafts_needing_first_reminder($post_types = array('post')) {
        $two_days_ago = date('Y-m-d H:i:s', strtotime('-2 days'));

        $args = array(
            'post_status'  => 'draft',
            'post_type'    => $post_types,
            'date_query'   => array(
                array(
                    'before'    => $two_days_ago,
                    'inclusive' => true,
                ),
            ),
            'meta_query'   => array(
                array(
                    'key'     => WP_DRAFTS_REMINDER_FIRST_REMINDER_META,
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'posts_per_page' => -1,
            'fields'         => 'all',
        );

        return get_posts($args);
    }

    /**
     * Get draft posts needing a second reminder (modified > 9 days ago, first reminder sent, second not sent).
     *
     * @param array $post_types Array of post type slugs to query.
     * @return array Array of post objects.
     */
    private function get_drafts_needing_second_reminder($post_types = array('post')) {
        $nine_days_ago = date('Y-m-d H:i:s', strtotime('-9 days'));

        $args = array(
            'post_status'  => 'draft',
            'post_type'    => $post_types,
            'date_query'   => array(
                array(
                    'before'    => $nine_days_ago,
                    'inclusive' => true,
                ),
            ),
            'meta_query'   => array(
                'relation' => 'AND',
                array(
                    'key'     => WP_DRAFTS_REMINDER_FIRST_REMINDER_META,
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => WP_DRAFTS_REMINDER_SECOND_REMINDER_META,
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'posts_per_page' => -1,
            'fields'         => 'all',
        );

        return get_posts($args);
    }

    /**
     * Get all draft posts for the configured post types (for admin display).
     * Also aliased as get_old_draft_posts for backward compatibility.
     *
     * @return array Array of post objects.
     */
    public function get_old_draft_posts() {
        return $this->get_all_draft_posts();
    }

    /**
     * Get all draft posts for the configured post types (internal use).
     *
     * @return array Array of post objects.
     */
    private function get_all_draft_posts() {
        $post_types = $this->get_monitored_post_types();

        $args = array(
            'post_status'  => 'draft',
            'post_type'    => $post_types,
            'posts_per_page' => -1,
            'fields'         => 'all',
        );

        return get_posts($args);
    }

    /**
     * Send reminder email to author using template file.
     *
     * @param int   $author_id Author user ID
     * @param array $drafts    Array of draft post objects
     * @param string $reminder_type 'first' or 'second'
     */
    public function send_reminder_email($author_id, $drafts, $reminder_type = 'first') {
        $author = get_userdata($author_id);

        if (!$author || !$author->user_email) {
            return;
        }

        $subject = $this->get_reminder_subject($reminder_type);
        $message = $this->load_email_template($author, $drafts, $reminder_type);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($author->user_email, $subject, $message, $headers);

        // Record when the reminder was sent
        foreach ($drafts as $draft) {
            if ($reminder_type === 'first') {
                update_post_meta($draft->ID, WP_DRAFTS_REMINDER_FIRST_REMINDER_META, current_time('mysql'));
            } elseif ($reminder_type === 'second') {
                update_post_meta($draft->ID, WP_DRAFTS_REMINDER_SECOND_REMINDER_META, current_time('mysql'));
            }
        }
    }

    /**
     * Get the email subject based on reminder type.
     *
     * @param string $reminder_type 'first' or 'second'
     * @return string Email subject
     */
    private function get_reminder_subject($reminder_type) {
        $site_name = get_bloginfo('name');
        if ($reminder_type === 'second') {
            return sprintf('Zweite Entwurfs-Erinnerung von %s', $site_name);
        }
        return sprintf('Entwurfs-Erinnerung von %s', $site_name);
    }

    /**
     * Load and render an email template.
     * Checks active theme's wp-drafts-reminder/ folder first,
     * then falls back to plugin templates, then inline fallback.
     *
     * @param WP_User $author        Author user object
     * @param array   $drafts        Array of draft post objects
     * @param string  $reminder_type 'first' or 'second'
     * @return string Email message HTML
     */
    private function load_email_template($author, $drafts, $reminder_type = 'first') {
        // Check theme override first
        $theme_template = get_stylesheet_directory() . '/wp-drafts-reminder/' . $reminder_type . '-reminder-email.php';
        if (file_exists($theme_template)) {
            ob_start();
            include $theme_template;
            $html = ob_get_clean();
            if (!empty($html)) {
                return $html;
            }
        }

        // Fall back to plugin template
        $template_file = WP_DRAFTS_REMINDER_PLUGIN_DIR . 'templates/' . $reminder_type . '-reminder-email.php';
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        }

        // Final fallback: inline HTML
        return $this->fallback_email_message($author, $drafts, $reminder_type);
    }

    /**
     * Fallback email message (inline HTML) if template is missing.
     *
     * @param WP_User $author        Author user object
     * @param array   $drafts        Array of draft post objects
     * @param string  $reminder_type 'first' or 'second'
     * @return string Email message HTML
     */
    private function fallback_email_message($author, $drafts, $reminder_type = 'first') {
        $message = '<p>' . sprintf('Hallo %s,', esc_html($author->display_name)) . '</p>';

        if ($reminder_type === 'second') {
            $message .= '<p>Sie haben die folgenden Entwürfe, die seit mehr als einer Woche unveröffentlicht sind — das ist Ihre zweite Erinnerung:</p>';
        } else {
            $message .= '<p>Sie haben die folgenden unveröffentlichten Entwürfe, die seit mehr als zwei Tagen nicht bearbeitet wurden:</p>';
        }

        $message .= '<ul>';
        foreach ($drafts as $draft) {
            $edit_link = get_edit_post_link($draft->ID);
            $days_old = $this->get_days_since_modified($draft->post_modified);

            $message .= sprintf(
                '<li><a href="%s">%s</a> <span style="color:#646970;">(%s)</span></li>',
                esc_url($edit_link),
                esc_html($draft->post_title ?: '(Kein Titel)'),
                sprintf($days_old === 1 ? '%d Tag alt' : '%d Tage alt', $days_old)
            );
        }
        $message .= '</ul>';

        if ($reminder_type === 'second') {
            $message .= '<p>Dies ist Ihre zweite Erinnerung. Bitte überlegen Sie, diese Entwürfe abzuschließen oder zu löschen, falls sie nicht mehr benötigt werden.</p>';
        } else {
            $message .= '<p>Bitte überlegen Sie, diese Entwürfe abzuschließen oder zu löschen, falls sie nicht mehr benötigt werden.</p>';
        }

        $message .= '<p>' . sprintf('Mit freundlichen Grüßen,<br>%s', esc_html(get_bloginfo('name'))) . '</p>';

        return $message;
    }

    /**
     * Calculate days since post was last modified
     *
     * @param string $post_modified Post modified date
     * @return int Number of days
     */
    private function get_days_since_modified($post_modified) {
        $modified_time = strtotime($post_modified);
        $current_time = current_time('timestamp');
        $diff = $current_time - $modified_time;
        
        return floor($diff / (60 * 60 * 24));
    }



    /**
     * Add admin submenu pages for settings, email preview, and drafts management.
     */
    public function add_admin_menu() {
        add_options_page(
            __('Drafts Reminder', 'wp-drafts-reminder'),
            __('Drafts Reminder', 'wp-drafts-reminder'),
            'manage_options',
            'wp-drafts-reminder',
            array($this, 'admin_page')
        );

        // Add submenu pages
        add_submenu_page(
            'wp-drafts-reminder',
            __('Settings', 'wp-drafts-reminder'),
            __('Settings', 'wp-drafts-reminder'),
            'manage_options',
            'wp-drafts-reminder-settings',
            function() {
                require_once WP_DRAFTS_REMINDER_PLUGIN_DIR . 'admin/settings.php';
                wdr_render_settings_page($this);
            }
        );

        add_submenu_page(
            'wp-drafts-reminder',
            __('Email Preview', 'wp-drafts-reminder'),
            __('Email Preview', 'wp-drafts-reminder'),
            'manage_options',
            'wp-drafts-reminder-preview',
            function() {
                require_once WP_DRAFTS_REMINDER_PLUGIN_DIR . 'admin/email-preview.php';
                wdr_render_email_preview($this);
            }
        );

        add_submenu_page(
            'wp-drafts-reminder',
            __('Drafts Management', 'wp-drafts-reminder'),
            __('Drafts Management', 'wp-drafts-reminder'),
            'manage_options',
            'wp-drafts-reminder-drafts',
            function() {
                require_once WP_DRAFTS_REMINDER_PLUGIN_DIR . 'admin/drafts.php';
                wdr_render_drafts_page($this);
            }
        );
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $old_drafts = $this->get_old_draft_posts();
        $first_pending = $this->get_drafts_needing_first_reminder();
        $second_pending = $this->get_drafts_needing_second_reminder();
        $next_scheduled = wp_next_scheduled('wp_drafts_reminder_check');
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
        $success_notice = '';
        
        if ($message === 'sent') {
            $success_notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Reminder emails have been sent to all affected authors.', 'wp-drafts-reminder') . '</p></div>';
        } elseif ($message === 'no-drafts') {
            $success_notice = '<div class="notice notice-info is-dismissible"><p>' . esc_html__('No drafts found that need reminders.', 'wp-drafts-reminder') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php echo $success_notice; ?>

            <div class="card">
                <h2><?php _e('Plugin Status', 'wp-drafts-reminder'); ?></h2>
                <p>
                    <strong><?php _e('Next scheduled check:', 'wp-drafts-reminder'); ?></strong>
                    <?php echo $next_scheduled ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled) : __('Not scheduled', 'wp-drafts-reminder'); ?>
                </p>
                <p>
                    <strong><?php _e('First reminders pending:', 'wp-drafts-reminder'); ?></strong>
                    <?php echo count($first_pending); ?>
                </p>
                <p>
                    <strong><?php _e('Second reminders pending:', 'wp-drafts-reminder'); ?></strong>
                    <?php echo count($second_pending); ?>
                </p>
                <p>
                    <strong><?php _e('Old drafts found:', 'wp-drafts-reminder'); ?></strong>
                    <?php echo count($old_drafts); ?>
                </p>
            </div>

            <div class="card">
                <h2><?php _e('Drafts Needing First Reminder', 'wp-drafts-reminder'); ?></h2>
                <?php if (empty($first_pending)) : ?>
                    <p><?php _e('No drafts need a first reminder.', 'wp-drafts-reminder'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Author', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Last Modified', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Days Old', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('First Reminder Sent', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Second Reminder Sent', 'wp-drafts-reminder'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($first_pending as $draft) : 
                                $author = get_userdata($draft->post_author);
                                $days_old = $this->get_days_since_modified($draft->post_modified);
                                $first_sent = get_post_meta($draft->ID, WP_DRAFTS_REMINDER_FIRST_REMINDER_META, true);
                                $second_sent = get_post_meta($draft->ID, WP_DRAFTS_REMINDER_SECOND_REMINDER_META, true);
                            ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($draft->ID)); ?>">
                                            <?php echo esc_html($draft->post_title ?: __('(No title)', 'wp-drafts-reminder')); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($author ? $author->display_name : __('Unknown', 'wp-drafts-reminder')); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($draft->post_modified))); ?></td>
                                    <td><?php echo esc_html($days_old); ?></td>
                                    <td><?php echo $first_sent ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($first_sent))) : __('No', 'wp-drafts-reminder'); ?></td>
                                    <td><?php echo $second_sent ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($second_sent))) : __('No', 'wp-drafts-reminder'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><?php _e('Drafts Needing Second Reminder', 'wp-drafts-reminder'); ?></h2>
                <?php if (empty($second_pending)) : ?>
                    <p><?php _e('No drafts need a second reminder.', 'wp-drafts-reminder'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Author', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Last Modified', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Days Old', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('First Reminder Sent', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Second Reminder Sent', 'wp-drafts-reminder'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($second_pending as $draft) : 
                                $author = get_userdata($draft->post_author);
                                $days_old = $this->get_days_since_modified($draft->post_modified);
                                $first_sent = get_post_meta($draft->ID, WP_DRAFTS_REMINDER_FIRST_REMINDER_META, true);
                                $second_sent = get_post_meta($draft->ID, WP_DRAFTS_REMINDER_SECOND_REMINDER_META, true);
                            ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($draft->ID)); ?>">
                                            <?php echo esc_html($draft->post_title ?: __('(No title)', 'wp-drafts-reminder')); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($author ? $author->display_name : __('Unknown', 'wp-drafts-reminder')); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($draft->post_modified))); ?></td>
                                    <td><?php echo esc_html($days_old); ?></td>
                                    <td><?php echo $first_sent ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($first_sent))) : __('No', 'wp-drafts-reminder'); ?></td>
                                    <td><?php echo $second_sent ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($second_sent))) : __('No', 'wp-drafts-reminder'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><?php _e('Manual Test', 'wp-drafts-reminder'); ?></h2>
                <p><?php _e('Click the button below to manually trigger the draft reminder check and send emails.', 'wp-drafts-reminder'); ?></p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('wp_drafts_reminder_test', 'wp_drafts_reminder_nonce'); ?>
                    <input type="hidden" name="action" value="wp_drafts_reminder_test">
                    <p>
                        <input type="submit" class="button button-primary" value="<?php _e('Send Test Reminders Now', 'wp-drafts-reminder'); ?>">
                    </p>
                </form>
            </div>

            <div class="card">
                <h2><?php _e('Reminder Schedule', 'wp-drafts-reminder'); ?></h2>
                <table class="widefat">
                    <tr>
                        <th><?php _e('Reminder', 'wp-drafts-reminder'); ?></th>
                        <th><?php _e('Trigger', 'wp-drafts-reminder'); ?></th>
                    </tr>
                    <tr>
                        <td><?php _e('First Reminder', 'wp-drafts-reminder'); ?></td>
                        <td><?php _e('2 days after last modification', 'wp-drafts-reminder'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Second Reminder', 'wp-drafts-reminder'); ?></td>
                        <td><?php _e('9 days after last modification (7 days after first)', 'wp-drafts-reminder'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Handle manual test action.
     *
     * @return void
     */
    public function handle_test_action() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        if (!isset($_POST['wp_drafts_reminder_nonce']) || !wp_verify_nonce($_POST['wp_drafts_reminder_nonce'], 'wp_drafts_reminder_test')) {
            wp_die(__('Security check failed.'));
        }

        // Check if this is a test send from the email preview page
        $test_type = isset($_POST['wdr_test_type']) ? sanitize_text_field($_POST['wdr_test_type']) : '';
        
        if ($test_type === 'first' || $test_type === 'second') {
            // Test send: send a single test email to the current admin user
            $admin_user = wp_get_current_user();
            if ($admin_user && is_a($admin_user, 'WP_User') && $admin_user->user_email) {
                $sample_drafts = $this->get_sample_drafts_for_preview($test_type);
                $this->send_reminder_email($admin_user->ID, $sample_drafts, $test_type);
                wp_redirect(add_query_arg('message', 'test-sent', wp_get_referer()));
            } else {
                wp_die(__('No valid admin email found for test send.', 'wp-drafts-reminder'));
            }
            exit;
        }

        // Regular test: run the full cron check
        $old_drafts = $this->get_old_draft_posts();
        
        if (empty($old_drafts)) {
            wp_redirect(add_query_arg('message', 'no-drafts', wp_get_referer()));
            exit;
        }

        $this->check_and_send_reminders();
        
        wp_redirect(add_query_arg('message', 'sent', wp_get_referer()));
        exit;
    }

    /**
     * Handle draft deletion (clear reminder status).
     *
     * @return void
     */
    public function handle_delete_draft() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        if (!isset($_POST['wdr_delete_nonce']) || !wp_verify_nonce($_POST['wdr_delete_nonce'], 'wdr_delete_draft')) {
            wp_die(__('Security check failed.'));
        }

        $draft_id = isset($_POST['wdr_draft_id']) ? absint($_POST['wdr_draft_id']) : 0;
        if (!$draft_id) {
            wp_die(__('Invalid draft ID.'));
        }

        $post = get_post($draft_id);
        if (!$post || $post->post_status !== 'draft') {
            wp_die(__('Draft not found or not in draft status.'));
        }

        $monitored_types = $this->get_monitored_post_types();
        if (!in_array($post->post_type, $monitored_types, true)) {
            wp_die(__('This post type is not monitored by the reminder system.'));
        }

        // Remove reminder meta to allow re-reminding
        delete_post_meta($draft_id, WP_DRAFTS_REMINDER_FIRST_REMINDER_META);
        delete_post_meta($draft_id, WP_DRAFTS_REMINDER_SECOND_REMINDER_META);

        $redirect = add_query_arg('wdr_message', 'draft-deleted', wp_get_referer());
        wp_redirect($redirect);
        exit;
    }

    /**
     * Get template file paths for a reminder type.
     * Returns theme path first (if it exists), then plugin path.
     *
     * @param string $reminder_type 'first' or 'second'.
     * @return array Array with 'theme' and 'plugin' keys.
     */
    public function get_template_paths($reminder_type = 'first') {
        $theme_dir = get_stylesheet_directory();
        $theme_template = $theme_dir . '/wp-drafts-reminder/' . $reminder_type . '-reminder-email.php';
        $plugin_template = WP_DRAFTS_REMINDER_PLUGIN_DIR . 'templates/' . $reminder_type . '-reminder-email.php';

        return array(
            'theme'    => file_exists($theme_template) ? $theme_template : null,
            'plugin'   => file_exists($plugin_template) ? $plugin_template : null,
            'theme_dir' => $theme_dir . '/wp-drafts-reminder/',
        );
    }

    /**
     * Get sample draft posts for email preview.
     *
     * @param string $reminder_type 'first' or 'second'.
     * @return array Array of sample post objects.
     */
    public function get_sample_drafts_for_preview($reminder_type = 'first') {
        $post_types = $this->get_monitored_post_types();
        
        // Get real drafts for preview
        $args = array(
            'post_status'  => 'draft',
            'post_type'    => $post_types,
            'posts_per_page' => 5,
            'fields'         => 'all',
        );

        $drafts = get_posts($args);
        
        // If no real drafts, create sample data for preview
        if (empty($drafts)) {
            $sample_titles = array(
                'My Upcoming Product Launch Article',
                'Draft: Seasonal Marketing Campaign',
                'Untitled Draft',
                'Blog Post: Industry Trends 2024',
                'Work in Progress: Customer Case Study',
            );
            
            $samples = array();
            foreach ($sample_titles as $i => $title) {
                $samples[] = (object) array(
                    'ID'          => 0,
                    'post_title'  => $title,
                    'post_type'   => !empty($post_types) ? $post_types[0] : 'post',
                    'post_modified' => date('Y-m-d H:i:s', strtotime("-" . ($reminder_type === 'first' ? 3 : 10) . " days")),
                    'post_author' => get_current_user_id(),
                );
            }
            return $samples;
        }

        return $drafts;
    }

    /**
     * Load email template for preview (uses sample data).
     * Checks active theme's wp-drafts-reminder/ folder first,
     * then falls back to plugin templates.
     *
     * @param array  $drafts        Array of sample post objects.
     * @param string $reminder_type 'first' or 'second'.
     * @return string Email HTML content.
     */
    public function load_email_template_for_preview($drafts, $reminder_type = 'first') {
        // Get current admin user as sample author
        $admin_user = wp_get_current_user();
        if (!$admin_user || !is_a($admin_user, 'WP_User')) {
            $admin_user = (object) array('display_name' => 'Admin');
        }

        // Check theme override first
        $theme_template = get_stylesheet_directory() . '/wp-drafts-reminder/' . $reminder_type . '-reminder-email.php';
        if (file_exists($theme_template)) {
            ob_start();
            include $theme_template;
            $html = ob_get_clean();
            if (!empty($html)) {
                return $html;
            }
        }

        // Fall back to plugin template
        $template_file = WP_DRAFTS_REMINDER_PLUGIN_DIR . 'templates/' . $reminder_type . '-reminder-email.php';
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        }

        // Final fallback: generate HTML inline
        return $this->fallback_email_message($admin_user, $drafts, $reminder_type);
    }
}

// Initialize the plugin
WP_Drafts_Reminder::get_instance();