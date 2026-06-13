<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(get_bloginfo('name')); ?> — Zweite Entwurfs-Erinnerung</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #dba612, #b4880c);
            color: #ffffff;
            padding: 24px 32px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .email-header {
            background: linear-gradient(135deg, #dba612, #b4880c) !important;
        }
        .email-body {
            padding: 32px;
            color: #3c434a;
            line-height: 1.6;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 16px;
        }
        .intro {
            font-size: 15px;
            margin-bottom: 20px;
        }
        .draft-list {
            list-style: none;
            padding: 0;
            margin: 0 0 24px 0;
        }
        .draft-list li {
            padding: 12px 16px;
            margin-bottom: 8px;
            background: #f0f0f1;
            border-left: 4px solid #dba612;
            border-radius: 4px;
            font-size: 14px;
        }
        .draft-list li a {
            color: #2271b1;
            text-decoration: none;
            font-weight: 500;
        }
        .draft-list li a:hover {
            text-decoration: underline;
        }
        .draft-meta {
            color: #646970;
            font-size: 13px;
            display: block;
            margin-top: 4px;
        }
        .footer-note {
            font-size: 14px;
            color: #646970;
            margin-bottom: 24px;
        }
        .email-footer {
            background: #f0f0f1;
            padding: 20px 32px;
            text-align: center;
            font-size: 13px;
            color: #646970;
        }
        .cta-button {
            display: inline-block;
            padding: 12px 24px;
            background: #dba612;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 8px;
        }
        .cta-button:hover {
            background: #b4880c;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1><?php echo esc_html(get_bloginfo('name')); ?> — Zweite Entwurfs-Erinnerung</h1>
        </div>
        <div class="email-body">
            <p class="greeting">
                <?php printf('Hallo %s,', esc_html($author->display_name)); ?>
            </p>
            <p class="intro">
                Sie haben die folgenden Entwürfe, die seit mehr als einer Woche unveröffentlicht sind — das ist Ihre zweite Erinnerung:
            </p>
            <ul class="draft-list">
                <?php foreach ($drafts as $draft) : ?>
                    <?php $edit_link = get_edit_post_link($draft->ID); ?>
                    <?php $days_old = $this->get_days_since_modified($draft->post_modified); ?>
                    <li>
                        <a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html($draft->post_title ?: __('(No title)', 'wp-drafts-reminder')); ?></a>
                        <span class="draft-meta">
                            <?php printf($days_old === 1 ? '%d Tag alt' : '%d Tage alt', $days_old); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="footer-note">
                Dies ist Ihre zweite Erinnerung. Bitte überlegen Sie, diese Entwürfe abzuschließen oder zu löschen, falls sie nicht mehr benötigt werden.
            </p>
            <p style="text-align: center;">
                <a href="<?php echo esc_url(get_admin_url()); ?>" class="cta-button">
                    Zum Dashboard gehen
                </a>
            </p>
        </div>
        <div class="email-footer">
            <?php printf('Mit freundlichen Grüßen,<br>%s', esc_html(get_bloginfo('name'))); ?>
        </div>
    </div>
</body>
</html>
