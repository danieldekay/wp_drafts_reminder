# WP Drafts Reminder

A WordPress plugin that sends email notifications to authors who have draft posts older than a week.

## Description

WP Drafts Reminder helps keep your WordPress site's content pipeline moving by automatically notifying authors when they have draft posts that have been sitting unpublished for more than a week. Each author only receives notifications about their own drafts, maintaining privacy and relevance.

## Features

- **Automated Daily Checks**: Uses WordPress cron to check for old drafts daily
- **Author-Specific Notifications**: Authors only receive notifications about their own drafts
- **Detailed Email Reports**: Emails include post titles, edit links, and how long each draft has been sitting
- **Internationalization Ready**: Fully translatable with proper text domain
- **WordPress Best Practices**: Follows WordPress coding standards and security practices

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wp-drafts-reminder` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. The plugin will automatically start checking for old drafts daily

## How It Works

1. **Daily Cron Job**: The plugin schedules a daily WordPress cron job to check for old drafts
2. **Draft Detection**: Finds all draft posts that were last modified more than 7 days ago
3. **Author Grouping**: Groups the drafts by their respective authors
4. **Email Notifications**: Sends an HTML email to each author with their old drafts listed
5. **Helpful Information**: Each email includes:
   - Post titles with edit links
   - How many days each draft has been sitting
   - Encouragement to finish or remove old drafts

## Email Content

The notification emails include:
- Personalized greeting with the author's display name
- List of draft posts with direct edit links
- Age of each draft (e.g., "5 days old")
- Professional closing with site name

## Technical Details

- **WordPress Version**: Requires WordPress 4.0 or higher
- **PHP Version**: Requires PHP 5.6 or higher
- **Cron Schedule**: Runs daily using WordPress built-in cron system
- **Email Function**: Uses WordPress `wp_mail()` function for reliability
- **Security**: Follows WordPress security best practices including proper sanitization

## Hooks and Actions

The plugin uses these WordPress hooks:
- `init` - Initialize plugin and schedule cron
- `wp_drafts_reminder_check` - Custom cron hook for checking drafts
- `register_activation_hook` - Schedule cron on activation
- `register_deactivation_hook` - Clean up cron on deactivation

## Customization

The plugin is built with extensibility in mind. Developers can:
- Hook into the `wp_drafts_reminder_check` action
- Filter email content and recipients
- Modify the 7-day threshold through code modifications

## License

This plugin is licensed under the MIT License. See LICENSE file for details.

## Support

For support, feature requests, or bug reports, please visit the [GitHub repository](https://github.com/danieldekay/wp_drafts_reminder).