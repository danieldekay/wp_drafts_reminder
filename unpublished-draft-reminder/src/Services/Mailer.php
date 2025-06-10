<?php
namespace DanielDeKay\DraftReminder\Services;

class Mailer {
    public function send( int $author_id, array $posts ): void {
        $settings = get_option( 'draft_reminder_settings', [] );
        $user     = get_user_by( 'id', $author_id );

        // Exit if plugin disabled in settings, or user not found, or no posts for this author (shouldn't happen if called correctly)
        if ( empty( $settings['enabled'] ) || ! $user || empty( $posts ) ) {
            return;
        }

        $draft_count = count( $posts );
        // Use array_column to get titles, then join for a simple list. User spec has a more detailed render_list.
        // $draft_list_titles = array_column($posts, 'title');
        // $draft_list  = implode("
 - ", $draft_list_titles);
        // If it's a list of title (link), then render_list is better.

        $draft_list_plain = $this->render_list( $posts, 'plain' );
        $draft_list_html = $this->render_list( $posts, 'html' );


        // Default subject and body from settings or fallback
        $default_subject = sprintf(
            // translators: %d is the number of drafts.
            _n(
                'Reminder: You have %d unpublished draft awaiting your attention',
                'Reminder: You have %d unpublished drafts awaiting your attention',
                $draft_count,
                'unpublished-draft-reminder'
            ),
            $draft_count
        );
        $subject = !empty($settings['subject']) ? $settings['subject'] : $default_subject;

        $default_body = sprintf(
            // translators: %1$s is user display name, %2$d is draft count, %3$s is list of drafts, %4$s is site title.
            __( "Hello %1\$s,

You have %2\$d unpublished draft(s) on %4\$s:
%3\$s

Please review, publish, or delete them. Thank you.", 'unpublished-draft-reminder' ),
            '{user_displayname}', // Placeholder, will be replaced
            $draft_count,        // Actual count
            '{draft_list_plain}', // Placeholder
            '{site_title}'       // Placeholder
        );
        $body = !empty($settings['body']) ? $settings['body'] : $default_body;

        // Prepare replacements
        $replacements = [
            '{site_title}'       => get_bloginfo( 'name' ),
            '{user_firstname}'   => $user->first_name,
            '{user_lastname}'    => $user->last_name,
            '{user_displayname}' => $user->display_name,
            '{user_email}'       => $user->user_email, // Added user_email merge tag
            '{author_name}'      => $user->display_name, // Alias for {user_displayname} for backward compatibility or user preference
            '{draft_count}'      => $draft_count,
            '{draft_list_plain}' => $draft_list_plain,
            '{draft_list_html}'  => $draft_list_html, // Added HTML version of list
            // '{draft_list}' is ambiguous, so specific ones are better. For now, let's make {draft_list} default to plain.
            '{draft_list}'       => $draft_list_plain,
        ];

        $subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
        $body    = str_replace( array_keys( $replacements ), array_values( $replacements ), $body );

        // Apply filters
        $subject = apply_filters( 'draft_reminder_email_subject', $subject, $author_id, $posts, $user ); // Added $user to filter
        $body    = apply_filters( 'draft_reminder_email_body', $body, $author_id, $posts, $user );       // Added $user to filter

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
        // Potentially allow HTML emails if a specific merge tag for HTML list is used, or a setting.
        // For now, sticking to plain text as per original spec for wp_mail.
        // If {draft_list_html} was used in body, and user wants HTML email, a filter could change content-type.

        wp_mail(
            $user->user_email,
            $subject,
            $body,
            $headers
        );
    }

    private function render_list( array $posts, string $type = 'plain' ): string {
        $items = [];
        foreach ( $posts as $p ) {
            $title = $p['title']; // Already has fallback for (no title) from DraftFinder
            $link  = get_edit_post_link( $p['ID'], 'raw' ); // Use 'raw' for non-display context

            if ($type === 'html') {
                $items[] = sprintf(
                    '<li><a href="%s">%s</a></li>',
                    esc_url( $link ),
                    esc_html( $title )
                );
            } else { // plain
                $items[] = sprintf(
                    "- %s (%s)",
                    $title, // Already plain text
                    $link // URL itself is plain
                );
            }
        }

        if ($type === 'html') {
            return '<ul>' . implode( "", $items ) . '</ul>';
        }
        return implode( "
", $items );
    }
}
