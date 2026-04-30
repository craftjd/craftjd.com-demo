<?php

return [
    'features' => [
        'blog' => false,
        'user_accounts' => false,
        'campaign_tracking' => false,
        'flat_file_content' => true,
    ],
    'mail_driver' => 'log',
    'filesystem_disk' => 'public',
    'admin_email' => env('ADMIN_EMAIL', 'you@example.com'),
];
