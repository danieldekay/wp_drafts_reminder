<?php
/**
 * Plugin initialisation.
 *
 * @package Unpublished_Draft_Reminder
 * @author  Daniel DeKay
 * @since   0.1.0
 */

namespace DanielDeKay\DraftReminder;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin initialisation class.
 *
 * @since 0.1.0
 */
class Plugin {

    /**
     * Initialise the plugin.
     *
     * @return void
     * @since 0.1.0
     */
    public static function init(): void {
        // Dummy implementation for now.
        // This will be replaced with actual initialisation code later.
        if ( is_admin() ) {
            add_action( 'admin_notices', static function () {
                echo '<div class="notice notice-success is-dismissible"><p>';
                esc_html_e( 'Unpublished Draft Reminder plugin is active (dummy implementation)!', 'unpublished-draft-reminder' );
                echo '</p></div>';
            } );
        }
    }
}
