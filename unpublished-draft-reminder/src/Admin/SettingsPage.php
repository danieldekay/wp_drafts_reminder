<?php
namespace DanielDeKay\DraftReminder\Admin;

class SettingsPage {
    private const OPT_KEY = 'draft_reminder_settings';

    public function register(): void {
        add_options_page(
            __( 'Draft Reminder', 'unpublished-draft-reminder' ),
            __( 'Draft Reminder', 'unpublished-draft-reminder' ),
            'manage_options',
            'draft-reminder',
            [ $this, 'render' ]
        );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function register_settings(): void {
        register_setting(
            'draft_reminder_group', // Option group. Matches settings_fields() in render()
            self::OPT_KEY,          // Option name to store in wp_options
            [ 'sanitize_callback' => [ $this, 'sanitize' ] ]
        );

        add_settings_section(
            'draft_reminder_main_section', // ID for the section
            __( 'Reminder Settings', 'unpublished-draft-reminder' ), // Title of the section
            '__return_false', // Callback to display the header of the section (none needed here)
            'draft-reminder'    // Page slug where this section will be displayed
        );

        add_settings_field(
            'enabled', // Field ID
            __( 'Enable Reminder', 'unpublished-draft-reminder' ), // Field title
            [ $this, 'render_enabled_field' ], // Callback to render the field
            'draft-reminder', // Page slug
            'draft_reminder_main_section', // Section ID
            [ 'label_for' => 'draft_reminder_enabled' ] // Arguments for the callback
        );

        add_settings_field(
            'post_types', // Field ID
            __( 'Post Types to Monitor', 'unpublished-draft-reminder' ), // Field title
            [ $this, 'render_post_types_field' ], // Callback to render the field
            'draft-reminder', // Page slug
            'draft_reminder_main_section' // Section ID
        );

        add_settings_field(
            'age_days', // Field ID
            __( 'Draft Age Threshold (Days)', 'unpublished-draft-reminder' ), // Field title
            [ $this, 'render_age_days_field' ], // Callback to render the field
            'draft-reminder', // Page slug
            'draft_reminder_main_section', // Section ID
            [ 'label_for' => 'draft_reminder_age_days' ] // Arguments for the callback
        );

        add_settings_field(
            'weekday', // Field ID
            __( 'Day to Send Emails', 'unpublished-draft-reminder' ), // Field title
            [ $this, 'render_weekday_field' ], // Callback to render the field
            'draft-reminder', // Page slug
            'draft_reminder_main_section', // Section ID
            [ 'label_for' => 'draft_reminder_weekday' ] // Arguments for the callback
        );

        add_settings_field(
            'time', // Field ID
            __( 'Time to Send Emails', 'unpublished-draft-reminder' ), // Field title
            [ $this, 'render_time_field' ], // Callback to render the field
            'draft-reminder', // Page slug
            'draft_reminder_main_section', // Section ID
            [ 'label_for' => 'draft_reminder_time' ] // Arguments for the callback
        );

        add_settings_field(
            'subject', // Field ID
            __( 'Email Subject', 'unpublished-draft-reminder' ), // Field title
            [ $this, 'render_subject_field' ], // Callback to render the field
            'draft-reminder', // Page slug
            'draft_reminder_main_section', // Section ID
            [ 'label_for' => 'draft_reminder_subject' ] // Arguments for the callback
        );

        add_settings_field(
            'body', // Field ID
            __( 'Email Body', 'unpublished-draft-reminder' ), // Field title
            [ $this, 'render_body_field' ], // Callback to render the field
            'draft-reminder', // Page slug
            'draft_reminder_main_section', // Section ID
            [ 'label_for' => 'draft_reminder_body' ] // Arguments for the callback
        );
    }

    public function render_body_field( array $args ): void {
        $options = get_option( self::OPT_KEY, [] );
        $default_body = sprintf(
            esc_html__( "Hello {user_displayname},

You have {draft_count} unpublished draft(s) on {site_title} that may require your attention.

Please review your drafts here: %s

Thank you.", 'unpublished-draft-reminder' ),
            esc_url( admin_url( 'edit.php?post_status=draft&author=' . get_current_user_id() ) ) // Note: This link will be for the current admin viewing the settings, not the recipient. This is a placeholder.
        );
        $body = isset( $options['body'] ) ? $options['body'] : $default_body;

        ?>
        <textarea id="<?php echo esc_attr( $args['label_for'] ); ?>"
                  name="<?php echo esc_attr( self::OPT_KEY . '[body]' ); ?>"
                  rows="10" class="large-text"><?php echo esc_textarea( $body ); ?></textarea>
        <p class="description">
            <?php esc_html_e( 'Enter the content for reminder emails. You can use HTML.', 'unpublished-draft-reminder' ); ?>
        </p>
        <?php
    }

    public function render_subject_field( array $args ): void {
        $options = get_option( self::OPT_KEY, [] );
        $subject = isset( $options['subject'] ) ? $options['subject'] : __( 'Reminder: {draft_count} drafts awaiting your attention', 'unpublished-draft-reminder' );
        ?>
        <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="<?php echo esc_attr( self::OPT_KEY . '[subject]' ); ?>"
               value="<?php echo esc_attr( $subject ); ?>"
               class="large-text">
        <p class="description">
            <?php esc_html_e( 'Enter the subject line for reminder emails.', 'unpublished-draft-reminder' ); ?>
        </p>
        <?php
    }

    public function render_time_field( array $args ): void {
        $options = get_option( self::OPT_KEY, [] );
        // Default to 08:00 in site's timezone if not set.
        $default_time = '08:00';
        if ( function_exists( 'wp_timezone' ) ) {
            // Try to make it a DateTime object to format, then get time string.
            // This is a bit more robust for defaulting if we wanted to default to current time.
            // For a fixed default like '08:00', it's simpler.
        }
        $time = isset( $options['time'] ) ? $options['time'] : $default_time;
        // Ensure the time is in HH:MM format for the input field
        if ( ! preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time ) ) {
            $time = $default_time; // Fallback to default if format is incorrect
        }

        ?>
        <input type="time" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="<?php echo esc_attr( self::OPT_KEY . '[time]' ); ?>"
               value="<?php echo esc_attr( $time ); ?>"
               class="short-text">
        <p class="description">
            <?php
            $offset_desc = '';
            if ( function_exists( 'wp_timezone_string' ) ) {
                $offset_desc = sprintf(
                    // translators: %s is the timezone string like "America/New_York" or UTC offset like "+05:30"
                    esc_html__( 'Uses your site timezone: %s.', 'unpublished-draft-reminder' ),
                    esc_html( wp_timezone_string() )
                );
            }
            echo esc_html_e( 'Select the time of day to send reminder emails.', 'unpublished-draft-reminder' ) . ' ' . esc_html($offset_desc);
            ?>
        </p>
        <?php
    }

    public function render_weekday_field( array $args ): void {
        global $wp_locale;
        $options = get_option( self::OPT_KEY, [] );
        // Default to site's configured start_of_week if not set, else 1 (Monday) if start_of_week is 0 (Sunday) to make Monday the default for many.
        // WordPress's get_option( 'start_of_week' ) returns 0 for Sunday, 1 for Monday, etc.
        // Our field will store 0 for Sunday, 1 for Monday, ..., 6 for Saturday.
        $default_weekday = (int) get_option( 'start_of_week', 1 );
        $weekday = isset( $options['weekday'] ) ? (int) $options['weekday'] : $default_weekday;

        ?>
        <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
                name="<?php echo esc_attr( self::OPT_KEY . '[weekday]' ); ?>">
            <?php for ( $i = 0; $i <= 6; $i++ ) : ?>
                <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $weekday, $i ); ?>>
                    <?php echo esc_html( $wp_locale->get_weekday( $i ) ); ?>
                </option>
            <?php endfor; ?>
        </select>
        <p class="description">
            <?php esc_html_e( 'Select the day of the week to send reminder emails.', 'unpublished-draft-reminder' ); ?>
        </p>
        <?php
    }

    public function render_age_days_field( array $args ): void {
        $options = get_option( self::OPT_KEY, [] );
        $age_days = isset( $options['age_days'] ) ? (int) $options['age_days'] : 7; // Default 7
        ?>
        <input type="number" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="<?php echo esc_attr( self::OPT_KEY . '[age_days]' ); ?>"
               value="<?php echo esc_attr( $age_days ); ?>"
               min="1" class="small-text">
        <p class="description">
            <?php esc_html_e( 'Send reminders for drafts older than this many days.', 'unpublished-draft-reminder' ); ?>
        </p>
        <?php
    }

    public function render_post_types_field(): void {
        $options = get_option( self::OPT_KEY, [] );
        $selected_post_types = isset( $options['post_types'] ) && is_array( $options['post_types'] ) ? $options['post_types'] : ['post']; // Default ['post']

        $post_types_to_exclude = [ 'attachment', 'revision', 'nav_menu_item' ];
        $all_post_types = get_post_types( [ 'public' => true ], 'objects' );
        ?>
        <fieldset>
            <legend class="screen-reader-text"><span><?php esc_html_e( 'Post Types to Monitor', 'unpublished-draft-reminder' ); ?></span></legend>
            <?php
            foreach ( $all_post_types as $post_type_obj ) {
                if ( in_array( $post_type_obj->name, $post_types_to_exclude, true ) ) {
                    continue;
                }
                $id = 'post_type_' . esc_attr( $post_type_obj->name );
                $checked = in_array( $post_type_obj->name, $selected_post_types, true );
                ?>
                <label for="<?php echo $id; ?>">
                    <input type="checkbox" id="<?php echo $id; ?>"
                           name="<?php echo esc_attr( self::OPT_KEY . '[post_types][]' ); ?>"
                           value="<?php echo esc_attr( $post_type_obj->name ); ?>"
                           <?php checked( $checked, true ); ?>>
                    <?php echo esc_html( $post_type_obj->label ); ?>
                </label><br>
                <?php
            }
            ?>
        </fieldset>
        <p class="description">
            <?php esc_html_e( 'Select the post types for which draft reminders should be sent.', 'unpublished-draft-reminder' ); ?>
        </p>
        <?php
    }

    public function render_enabled_field( array $args ): void {
        $options = get_option( self::OPT_KEY, [] );
        $enabled = isset( $options['enabled'] ) ? (bool) $options['enabled'] : true; // Default true
        ?>
        <input type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="<?php echo esc_attr( self::OPT_KEY . '[enabled]' ); ?>"
               value="1" <?php checked( $enabled, true ); ?>>
        <p class="description">
            <?php esc_html_e( 'Master on/off switch for all reminders.', 'unpublished-draft-reminder' ); ?>
        </p>
        <?php
    }

    public function sanitize( array $input ): array {
        $new_input = [];

        // Enabled: Checkbox (boolean)
        $new_input['enabled'] = ! empty( $input['enabled'] );

        // Post Types: Multi-select (checkbox list of strings)
        if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
            $new_input['post_types'] = array_unique( array_map( 'sanitize_key', $input['post_types'] ) );
        } else {
            $new_input['post_types'] = []; // Default to empty array if not set or not an array
        }
        // Ensure 'post' is a default if empty, as per original settings table (can be reconsidered)
        if ( empty( $new_input['post_types'] ) ) {
             $new_input['post_types'][] = 'post';
        }

        // Age (Days): Number (positive integer)
        $new_input['age_days'] = isset( $input['age_days'] ) ? absint( $input['age_days'] ) : 7;
        if ( $new_input['age_days'] < 1 ) {
            $new_input['age_days'] = 7; // Default to 7 if less than 1
        }

        // Weekday: Select (integer 0-6)
        $new_input['weekday'] = isset( $input['weekday'] ) ? absint( $input['weekday'] ) : (int) get_option( 'start_of_week', 1 );
        if ( $new_input['weekday'] < 0 || $new_input['weekday'] > 6 ) {
            $new_input['weekday'] = (int) get_option( 'start_of_week', 1 ); // Default to site's start_of_week
        }

        // Time: Time string (HH:MM)
        $new_input['time'] = '08:00'; // Default time
        if ( isset( $input['time'] ) && is_string( $input['time'] ) && preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input['time'] ) ) {
            $new_input['time'] = $input['time'];
        }

        // Subject: Text
        $default_subject = __( 'Reminder: {draft_count} drafts awaiting your attention', 'unpublished-draft-reminder' );
        $new_input['subject'] = isset( $input['subject'] ) ? sanitize_text_field( $input['subject'] ) : $default_subject;
        if ( empty( trim( $new_input['subject'] ) ) ) {
             $new_input['subject'] = $default_subject;
        }

        // Body: Textarea (HTML allowed by wp_kses_post)
        $default_body = sprintf(
            esc_html__( "Hello {user_displayname},

You have {draft_count} unpublished draft(s) on {site_title} that may require your attention.

Please review your drafts here: %s

Thank you.", 'unpublished-draft-reminder' ),
            esc_url( admin_url( 'edit.php?post_status=draft' ) ) // General link, not user specific for default
        );
        $new_input['body'] = isset( $input['body'] ) ? wp_kses_post( $input['body'] ) : $default_body;
         if ( empty( trim( $new_input['body'] ) ) ) {
             $new_input['body'] = $default_body;
        }

        return $new_input;
    }

    public function render(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Unpublished Draft Reminder', 'unpublished-draft-reminder' ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'draft_reminder_group' );
                do_settings_sections( 'draft-reminder' ); // Page slug for settings sections
                submit_button( __( 'Save Settings', 'unpublished-draft-reminder' ) );
                ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Test Email', 'unpublished-draft-reminder' ); ?></h2>
            <form method="post" action=""> <?php // Action will be handled by a hook later ?>
                <?php wp_nonce_field( 'draft_reminder_send_test_email_action', 'draft_reminder_send_test_email_nonce' ); ?>
                <input type="hidden" name="action" value="draft_reminder_send_test_email">
                <p>
                    <?php /* translators: Email address input field */ ?>
                    <label for="draft_reminder_test_email_recipient"><?php esc_html_e( 'Send a test email to:', 'unpublished-draft-reminder' ); ?></label>
                    <input type="email" id="draft_reminder_test_email_recipient" name="test_email_recipient"
                           value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="regular-text">
                </p>
                <?php submit_button( __( 'Send Test Email', 'unpublished-draft-reminder' ), 'secondary', 'draft_reminder_send_test_submit' ); ?>
                 <p class="description">
                    <?php esc_html_e( 'This will send a sample email based on your saved settings. The content will be generated for the current user if applicable (e.g., for {user_...} merge tags). The {draft_count} and {draft_list_...} will be simulated.', 'unpublished-draft-reminder' ); ?>
                </p>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Merge Tag Legend', 'unpublished-draft-reminder' ); ?></h2>
            <p><?php esc_html_e( 'Use these merge tags in your Email Subject and Body:', 'unpublished-draft-reminder' ); ?></p>
            <ul>
                <li><code>{site_title}</code> - <?php esc_html_e( 'Your website's title.', 'unpublished-draft-reminder' ); ?></li>
                <li><code>{user_firstname}</code> - <?php esc_html_e( 'The first name of the email recipient (author).', 'unpublished-draft-reminder' ); ?></li>
                <li><code>{user_lastname}</code> - <?php esc_html_e( 'The last name of the email recipient (author).', 'unpublished-draft-reminder' ); ?></li>
                <li><code>{user_displayname}</code> - <?php esc_html_e( 'The display name of the email recipient (author).', 'unpublished-draft-reminder' ); ?></li>
                <li><code>{user_email}</code> - <?php esc_html_e( 'The email address of the recipient (author).', 'unpublished-draft-reminder' ); ?></li>
                <li><code>{draft_count}</code> - <?php esc_html_e( 'The total number of unpublished drafts for the recipient.', 'unpublished-draft-reminder' ); ?></li>
                <li><code>{draft_list_plain}</code> - <?php esc_html_e( 'A plain text list of the draft titles and links.', 'unpublished-draft-reminder' ); ?></li>
                <li><code>{draft_list_html}</code> - <?php esc_html_e( 'An HTML list of the draft titles and links.', 'unpublished-draft-reminder' ); ?></li>
            </ul>
            <p><?php esc_html_e( 'The draft list links will direct the user to their own drafts in the WordPress admin area.', 'unpublished-draft-reminder' ); ?></p>

        </div>
        <?php
    }
}
