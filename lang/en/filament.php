<?php

return [
    'activity_log' => [
        'navigation_label' => 'Activity Log',
        'heading' => 'Activity Log',
        'empty_heading' => 'No activity found',
        'columns' => [
            'date' => 'Date',
            'user' => 'User',
            'action' => 'Action',
            'subject' => 'Subject',
        ],
        'filters' => [
            'user' => 'User',
            'action' => 'Action',
            'date' => 'Date range',
        ],
    ],

    'suspicious_activity' => [
        'navigation_label' => 'Suspicious Activity',
        'heading' => 'Suspicious Activity',
        'empty_heading' => 'No suspicious activity found',
        'columns' => [
            'date' => 'Date',
            'severity' => 'Severity',
            'type' => 'Type',
            'reason' => 'Reason',
            'report' => 'Report UUID',
        ],
        'filters' => [
            'type' => 'Type',
            'severity' => 'Severity',
            'date' => 'Date range',
        ],
    ],
];
