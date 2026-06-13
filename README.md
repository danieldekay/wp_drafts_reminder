# WP Drafts Reminder

A WordPress plugin that sends **two-tiered** email reminders to authors who have draft posts sitting unpublished.

## Description

WP Drafts Reminder helps keep your WordPress site's content pipeline moving by automatically notifying authors when they have draft posts that have been sitting unpublished. The plugin sends **two reminders** at configurable intervals:

1. **First reminder** — 2 days after the post was last modified
2. **Second reminder** — 7 days after the first reminder (9 days after last modification)

Each author only receives notifications about their own drafts, maintaining privacy and relevance.

## Features

- **Two-Tiered Reminders**: First reminder at 2 days, second at 9 days (7 days after first)
- **Automated Daily Checks**: Uses WordPress cron to check for old drafts daily
- **Author-Specific Notifications**: Authors only receive notifications about their own drafts
- **Reminder Tracking**: Uses post meta to track which reminders have been sent, avoiding duplicates
- **Detailed Email Reports**: Emails include post titles, edit links, and how long each draft has been sitting
- **Internationalization Ready**: Fully translatable with proper text domain
- **Admin Dashboard**: View pending reminders, scheduled checks, and manually trigger tests
- **WordPress Best Practices**: Follows WordPress coding standards and security practices

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wp-drafts-reminder` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. The plugin will automatically start checking for drafts daily

## How It Works

1. **Daily Cron Job**: The plugin schedules a daily WordPress cron job to check for old drafts
2. **Two-Tiered Detection**:
   - **First reminders**: Drafts modified more than 2 days ago with no first reminder sent
   - **Second reminders**: Drafts modified more than 9 days ago, first reminder sent, second not yet sent
3. **Author Grouping**: Groups the drafts by their respective authors
4. **Email Notifications**: Sends a personalized HTML email to each author listing their drafts
5. **Reminder Tracking**: Post meta (`_wdr_first_reminder_sent`, `_wdr_second_reminder_sent`) prevents duplicate emails

## Reminder Schedule

| Reminder | Trigger |
|----------|----------|
| First    | 2 days after last modification |
| Second   | 9 days after last modification (7 days after first) |

## Email Content

**First reminder email** includes:
- Personalized greeting with the author's display name
- List of draft posts with direct edit links
- Age of each draft (e.g., "3 days old")
- Encouragement to finish or remove drafts

**Second reminder email** includes:
- Same content as first, plus a note that this is the second reminder
- Urges the author to finish or remove drafts that have been sitting for over a week

## Admin Dashboard

Access **Settings → Drafts Reminder** to see:
- Next scheduled check time
- Number of drafts pending first and second reminders
- Full table of old drafts with title, author, modification date, age, and reminder status
- Manual "Send Test Reminders Now" button

## Technical Details

- **WordPress Version**: Requires WordPress 4.0 or higher
- **PHP Version**: Requires PHP 5.6 or higher
- **Cron Schedule**: Runs daily using WordPress built-in cron system
- **Email Function**: Uses WordPress `wp_mail()` function for reliability
- **Post Types**: Currently covers `post` (extensible for custom post types)
- **Security**: Follows WordPress security best practices including nonce verification, capability checks, and proper escaping

## Hooks and Actions

The plugin uses these WordPress hooks:
- `init` — Initialize plugin and schedule cron
- `wp_drafts_reminder_check` — Custom cron hook for checking drafts
- `register_activation_hook` — Schedule cron on activation
- `register_deactivation_hook` — Clean up cron on deactivation

## Customization

The plugin is built with extensibility in mind. Developers can:
- Hook into the `wp_drafts_reminder_check` action
- Filter email content and recipients
- Modify the reminder thresholds through code

## License

This plugin is licensed under the MIT License. See LICENSE file for details.

## Support

For support, feature requests, or bug reports, please visit the [GitHub repository](https://github.com/danieldekay/wp_drafts_reminder).