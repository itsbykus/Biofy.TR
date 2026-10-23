<?php

// Default texts; editable per locale in Admin → E-mail templates. Paragraphs are separated by a blank line.
return [
    'button_fallback' => 'If the button does not work, paste this link into your browser:',
    'footer' => 'This e-mail was sent by :site. If you did not request this, you can ignore it or contact our support team.',
    'welcome' => [
        'subject' => 'Welcome to {site}!',
        'body' => "Hi {name},\n\nYour {site} account is ready. You can now bring all your links together on one elegant page.\n\nStart by adding a profile picture, create your links and publish your page.",
        'action' => 'Go to dashboard',
    ],
    'verify_email' => [
        'subject' => 'Verify your e-mail address',
        'body' => "Hi {name},\n\nPlease verify your e-mail address to secure your account. This link is valid for {minutes} minutes.",
        'action' => 'Verify my e-mail',
    ],
    'reset_password' => [
        'subject' => 'Password reset request',
        'body' => "Hi {name},\n\nWe received a request to reset the password of your account. Use the button below to choose a new password. The link is valid for {minutes} minutes.\n\nIf you did not request this, no action is needed; your password will not change.",
        'action' => 'Reset my password',
    ],
    'security_alert' => [
        'subject' => 'Security change on your account',
        'body' => "Hi {name},\n\nThe following happened on your account: {event}\nTime: {time}\nIP address: {ip}\n\nIf this was not you, change your password immediately and contact support.",
        'action' => '',
    ],
    'announcement' => [
        'subject' => 'Announcement from {site}',
        'body' => "Hi {name},\n\n{message}",
        'action' => '',
    ],
    'account_suspended' => [
        'subject' => 'Your account has been suspended',
        'body' => "Hi {name},\n\nYour {site} account has been suspended. Your pages are not shown to visitors meanwhile.\n\nReason: {reason}\n\nIf you think this is a mistake, please contact support.",
        'action' => '',
    ],
    'account_reactivated' => [
        'subject' => 'Your account has been reactivated',
        'body' => "Hi {name},\n\nYour {site} account is active again as of {date}. You can sign in and manage your pages.",
        'action' => 'Sign in',
    ],
    'deletion_requested' => [
        'subject' => 'Account deletion request received',
        'body' => "Hi {name},\n\nWe received your account deletion request. Your account and all data will be permanently deleted on {date}.\n\nIf you change your mind, sign in before that date and cancel the request.",
        'action' => '',
    ],
    'contact_received' => [
        'subject' => '[{site}] New contact message',
        'body' => "A new contact message was received:\n\n{message}",
        'action' => 'View message',
    ],
    'test' => [
        'subject' => '{site} test e-mail',
        'body' => "This is a test e-mail.\n\nIf you can read this, your e-mail settings work.",
        'action' => '',
    ],
];
