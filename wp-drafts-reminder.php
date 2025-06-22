<?php
/**
 * Plugin Name: WP Drafts Reminder
 * Plugin URI: https://github.com/danieldekay/wp_drafts_reminder
 * Description: Sends email notifications to authors who have draft posts older than a week.
 * Version: 1.0.0
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
define('WP_DRAFTS_REMINDER_VERSION', '1.0.0');
define('WP_DRAFTS_REMINDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DRAFTS_REMINDER_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        
        // Add admin menu for testing (only for administrators)
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_post_wp_drafts_reminder_test', array($this, 'handle_test_action'));
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
     * Check for old drafts and send reminders
     */
    public function check_and_send_reminders() {
        $old_drafts = $this->get_old_draft_posts();
        
        if (empty($old_drafts)) {
            return;
        }

        // Group drafts by author
        $drafts_by_author = array();
        foreach ($old_drafts as $draft) {
            $author_id = $draft->post_author;
            if (!isset($drafts_by_author[$author_id])) {
                $drafts_by_author[$author_id] = array();
            }
            $drafts_by_author[$author_id][] = $draft;
        }

        // Send reminders to each author
        foreach ($drafts_by_author as $author_id => $drafts) {
            $this->send_reminder_email($author_id, $drafts);
        }
    }

    /**
     * Get draft posts older than a week
     *
     * @return array Array of post objects
     */
    private function get_old_draft_posts() {
        $one_week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $args = array(
            'post_status' => 'draft',
            'post_type' => 'post',
            'date_query' => array(
                array(
                    'before' => $one_week_ago,
                    'inclusive' => true,
                ),
            ),
            'posts_per_page' => -1,
            'fields' => 'all',
        );

        return get_posts($args);
    }

    /**
     * Send reminder email to author
     *
     * @param int   $author_id Author user ID
     * @param array $drafts    Array of draft post objects
     */
    private function send_reminder_email($author_id, $drafts) {
        $author = get_userdata($author_id);
        
        if (!$author || !$author->user_email) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: Site name */
            __('Draft Reminder from %s', 'wp-drafts-reminder'),
            get_bloginfo('name')
        );

        $message = $this->get_email_message($author, $drafts);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($author->user_email, $subject, $message, $headers);
    }

    /**
     * Generate email message content
     *
     * @param WP_User $author Author user object
     * @param array   $drafts Array of draft post objects
     * @return string Email message HTML
     */
    private function get_email_message($author, $drafts) {
        $message = sprintf(
            /* translators: %s: Author display name */
            '<p>' . __('Hello %s,', 'wp-drafts-reminder') . '</p>',
            esc_html($author->display_name)
        );

        $message .= '<p>' . __('You have the following draft posts that have been sitting for more than a week:', 'wp-drafts-reminder') . '</p>';
        
        $message .= '<ul>';
        foreach ($drafts as $draft) {
            $edit_link = get_edit_post_link($draft->ID);
            $days_old = $this->get_days_since_modified($draft->post_modified);
            
            $message .= sprintf(
                '<li><a href="%s">%s</a> - %s</li>',
                esc_url($edit_link),
                esc_html($draft->post_title),
                sprintf(
                    /* translators: %d: Number of days */
                    _n('%d day old', '%d days old', $days_old, 'wp-drafts-reminder'),
                    $days_old
                )
            );
        }
        $message .= '</ul>';

        $message .= '<p>' . __('Consider finishing these drafts or removing them if they are no longer needed.', 'wp-drafts-reminder') . '</p>';
        
        $message .= sprintf(
            '<p>' . __('Best regards,<br>%s', 'wp-drafts-reminder') . '</p>',
            esc_html(get_bloginfo('name'))
        );

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
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Drafts Reminder', 'wp-drafts-reminder'),
            __('Drafts Reminder', 'wp-drafts-reminder'),
            'manage_options',
            'wp-drafts-reminder',
            array($this, 'admin_page')
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
        $next_scheduled = wp_next_scheduled('wp_drafts_reminder_check');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="card">
                <h2><?php _e('Plugin Status', 'wp-drafts-reminder'); ?></h2>
                <p>
                    <strong><?php _e('Next scheduled check:', 'wp-drafts-reminder'); ?></strong>
                    <?php echo $next_scheduled ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled) : __('Not scheduled', 'wp-drafts-reminder'); ?>
                </p>
                <p>
                    <strong><?php _e('Old drafts found:', 'wp-drafts-reminder'); ?></strong>
                    <?php echo count($old_drafts); ?>
                </p>
            </div>

            <div class="card">
                <h2><?php _e('Current Old Drafts', 'wp-drafts-reminder'); ?></h2>
                <?php if (empty($old_drafts)) : ?>
                    <p><?php _e('No old drafts found. Great job!', 'wp-drafts-reminder'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Author', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Last Modified', 'wp-drafts-reminder'); ?></th>
                                <th><?php _e('Days Old', 'wp-drafts-reminder'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($old_drafts as $draft) : 
                                $author = get_userdata($draft->post_author);
                                $days_old = $this->get_days_since_modified($draft->post_modified);
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
        </div>
        <?php
    }

    /**
     * Handle manual test action
     */
    public function handle_test_action() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        if (!isset($_POST['wp_drafts_reminder_nonce']) || !wp_verify_nonce($_POST['wp_drafts_reminder_nonce'], 'wp_drafts_reminder_test')) {
            wp_die(__('Security check failed.'));
        }

        $old_drafts = $this->get_old_draft_posts();
        
        if (empty($old_drafts)) {
            wp_redirect(add_query_arg('message', 'no-drafts', wp_get_referer()));
            exit;
        }

        $this->check_and_send_reminders();
        
        wp_redirect(add_query_arg('message', 'sent', wp_get_referer()));
        exit;
    }
}

// Initialize the plugin
WP_Drafts_Reminder::get_instance();