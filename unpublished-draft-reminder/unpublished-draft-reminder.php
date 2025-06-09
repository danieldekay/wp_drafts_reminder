<?php
/**
 * Plugin Name: Unpublished Draft Reminder
 * Description: Emails authors weekly about unpublished drafts.
 * Version: 0.1.0
 * Author: Daniel DeKay
 * Text Domain: unpublished-draft-reminder
 * Requires PHP: 7.4
 * Requires at least: 5.9
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    add_action( 'admin_notices', static function () {
        echo '<div class="error"><p>';
        esc_html_e( 'Unpublished Draft Reminder requires PHP 7.4 or higher.', 'unpublished-draft-reminder' );
        echo '</p></div>';
    } );
    return;
}

// Composer autoload.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

// Initialise the plugin (dummy for now).
\DanielDeKay\DraftReminder\Plugin::init();
