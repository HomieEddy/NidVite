<?php

return [
    'status_updated' => [
        'subject' => 'Mise à jour de votre signalement — :status',
        'greeting' => 'Bonjour,',
        'body' => 'Le statut de votre signalement a changé de « :old » à « :new ».',
        'rejection_reason' => 'Raison du rejet',
        'track_button' => 'Suivre mon signalement',
        'unsubscribe_hint' => 'Vous pouvez arrêter ce suivi à tout moment :',
        'unsubscribe_button' => 'Se désabonner',
        'footer' => 'Vous recevez cet e-mail car vous avez soumis un signalement sur NidVite.',
        'signature' => 'Cordialement,',
    ],
    'weekly_digest' => [
        'subject' => 'Synthèse hebdomadaire des opérations (:start à :end)',
        'greeting' => 'Bonjour équipe opérations,',
        'body' => 'Voici la synthèse hebdomadaire des opérations pour la période du :start au :end.',
        'counts' => [
            'new' => 'Nouveaux signalements',
            'open' => 'Signalements ouverts',
            'resolved' => 'Signalements résolus',
        ],
        'hotspots' => [
            'neighborhoods' => 'Quartiers principaux',
            'zones' => 'Zones principales',
            'none' => 'Aucune donnée pour cette période.',
            'unknown_neighborhood' => 'Quartier inconnu',
        ],
        'footer' => 'Cette synthèse est générée automatiquement par les opérations NidVite.',
        'signature' => 'Cordialement,',
    ],
];
