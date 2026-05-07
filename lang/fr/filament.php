<?php

return [
    'activity_log' => [
        'navigation_label' => 'Journal d\'activite',
        'heading' => 'Journal d\'activite',
        'empty_heading' => 'Aucune activite trouvee',
        'columns' => [
            'date' => 'Date',
            'user' => 'Utilisateur',
            'action' => 'Action',
            'subject' => 'Sujet',
        ],
        'filters' => [
            'user' => 'Utilisateur',
            'action' => 'Action',
            'date' => 'Periode',
        ],
    ],

    'suspicious_activity' => [
        'navigation_label' => 'Activite suspecte',
        'heading' => 'Activite suspecte',
        'empty_heading' => 'Aucune activite suspecte trouvee',
        'columns' => [
            'date' => 'Date',
            'severity' => 'Severite',
            'type' => 'Type',
            'reason' => 'Raison',
            'report' => 'UUID du signalement',
        ],
        'filters' => [
            'type' => 'Type',
            'severity' => 'Severite',
            'date' => 'Periode',
        ],
    ],
];
