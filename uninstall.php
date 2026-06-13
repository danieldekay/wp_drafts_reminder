<?php
/**
 * Uninstall script for WP Drafts Reminder
 * This file is called when the plugin is deleted
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clear any scheduled cron events
wp_clear_scheduled_hook('wp_drafts_reminder_check');