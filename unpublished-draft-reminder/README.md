# Unpublished Draft Reminder

Contributors: Daniel DeKay
Tags: drafts, reminder, admin, email, author, schedule, unpublished
Requires at least: 5.9
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Emails authors weekly about their unpublished drafts, prompting them to review, publish, or delete old content.

## Description

This plugin helps keep your WordPress site tidy by reminding authors about their unpublished drafts. If a draft hasn't been updated for a configurable period (default 7 days), an email is sent to the author on a configured day and time. This encourages authors to either complete and publish their work or remove drafts that are no longer needed.

Site administrators can configure:
*   Whether reminders are enabled.
*   Which post types to monitor for stale drafts.
*   How old a draft must be (in days) to be considered stale.
*   The day of the week and time of day to send reminder emails.
*   The subject and body of the reminder email, with support for merge tags.

## Installation

1.  Upload the `unpublished-draft-reminder` directory to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to Settings > Draft Reminder to configure the plugin.

## Filters

The plugin provides the following filters for developers to customize its behavior:

### `draft_reminder_email_subject`

Allows modification of the email subject line before an email is sent.

**Parameters:**

*   `string $subject`: The generated email subject.
*   `int $author_id`: The ID of the author receiving the email.
*   `array $posts`: An array of post objects/data (each item typically `['ID' => int, 'title' => string]`) that are stale for this author.
*   `\WP_User $user`: The WP_User object for the recipient author.

**Example:**

```php
add_filter( 'draft_reminder_email_subject', function( $subject, $author_id, $posts, $user ) {
    // Prepend the site name to all subjects
    return get_bloginfo( 'name' ) . ' - ' . $subject;
}, 10, 4 );
```

### `draft_reminder_email_body`

Allows modification of the email body before an email is sent.

**Parameters:**

*   `string $body`: The generated email body.
*   `int $author_id`: The ID of the author receiving the email.
*   `array $posts`: An array of post objects/data (each item typically `['ID' => int, 'title' => string]`) that are stale for this author.
*   `\WP_User $user`: The WP_User object for the recipient author.

**Example:**

```php
add_filter( 'draft_reminder_email_body', function( $body, $author_id, $posts, $user ) {
    // Append a custom footer to all emails
    $custom_footer = "

---
Please also check our internal wiki for publishing guidelines.";
    return $body . $custom_footer;
}, 10, 4 );
```

## Frequently Asked Questions

### How are the reminder emails scheduled?
The plugin uses WordPress's built-in WP-Cron system. Emails are scheduled to be processed daily, at which point the plugin checks if it's the configured day and time to send actual reminders.

### What merge tags can I use in the email subject and body?
The settings page includes a legend for all available merge tags. Common tags include:
*   `{site_title}` - Your website's title.
*   `{user_displayname}` - The display name of the email recipient.
*   `{draft_count}` - The total number of unpublished drafts for the recipient.
*   `{draft_list_plain}` - A plain text list of the draft titles and links.
*   `{draft_list_html}` - An HTML list of the draft titles and links.

### Can I customize when the cron job itself runs?
The plugin schedules a daily WordPress cron event. If you need more precise control over when WordPress cron events fire (e.g., ensuring it runs exactly at the time you set in the plugin, rather than when site traffic next triggers WP-Cron), you might need to configure a server-level cron job to hit `wp-cron.php` regularly. This is a general WordPress consideration, not specific to this plugin.

## Changelog

### 0.1.0
* Initial development version. Includes settings page, draft finding logic, and basic email sending capability. Core features implemented. Filters for subject and body added. Text domain set up for localization. PSR-4 autoloading with Composer. Basic README.md.
