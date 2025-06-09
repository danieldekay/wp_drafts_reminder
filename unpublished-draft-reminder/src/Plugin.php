<?php
namespace DanielDeKay\DraftReminder;

// It's good practice to import used classes.
use DanielDeKay\DraftReminder\Admin\SettingsPage;

class Plugin {
    public static function init(): void {
        // Load text domain
        add_action( 'plugins_loaded', [ self::class, 'load_textdomain' ] );

        // Register admin settings page
        if ( is_admin() ) { // Only load admin classes if in admin area
            add_action( 'admin_menu', [ self::class, 'register_admin_menu' ] );
        }
    }

    public static function load_textdomain(): void {
        load_plugin_textdomain(
            'unpublished-draft-reminder',
            false,
            dirname( plugin_basename( __DIR__ . '/../unpublished-draft-reminder.php' ) ) . '/languages/'
        );
    }

    public static function register_admin_menu(): void {
        // We will create Admin/SettingsPage.php in the next steps.
        // For now, ensure the directory exists to avoid autoloader issues if it tries to load it.
        if (!is_dir(__DIR__ . '/Admin')) {
            mkdir(__DIR__ . '/Admin', 0755, true);
        }
        // Create a temporary placeholder file for SettingsPage to avoid fatal errors during this step.
        // This will be overwritten in the next step.
        if (!file_exists(__DIR__ . '/Admin/SettingsPage.php')) {
            file_put_contents(__DIR__ . '/Admin/SettingsPage.php', '<?php namespace DanielDeKay\DraftReminder\Admin; class SettingsPage { public function register() {} }');
        }

        ( new SettingsPage() )->register();
    }
}
