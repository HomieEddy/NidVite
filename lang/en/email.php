<?php

return [
    'status_updated' => [
        'subject' => 'Your Report Update — :status',
        'greeting' => 'Hello,',
        'body' => 'Your report status has changed from ":old" to ":new".',
        'rejection_reason' => 'Rejection reason',
        'track_button' => 'Track my report',
        'unsubscribe_hint' => 'You can stop following this report at any time:',
        'unsubscribe_button' => 'Unsubscribe',
        'footer' => 'You are receiving this email because you submitted a report on NidVite.',
        'signature' => 'Best regards,',
    ],
    'weekly_digest' => [
        'subject' => 'Weekly Operations Digest (:start to :end)',
        'greeting' => 'Hello operations team,',
        'body' => 'Here is your weekly digest for :start to :end.',
        'counts' => [
            'new' => 'New reports',
            'open' => 'Open reports',
            'resolved' => 'Resolved reports',
        ],
        'hotspots' => [
            'neighborhoods' => 'Top neighborhoods',
            'zones' => 'Top zones',
            'none' => 'No data for this period.',
            'unknown_neighborhood' => 'Unknown neighborhood',
        ],
        'footer' => 'This digest was generated automatically by NidVite operations.',
        'signature' => 'Regards,',
    ],
];
