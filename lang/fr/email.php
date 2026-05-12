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
        'subject' => 'Digest operations hebdomadaire (:start a :end)',
        'greeting' => 'Bonjour equipe operations,',
        'body' => 'Voici votre digest hebdomadaire pour la periode :start a :end.',
        'counts' => [
            'new' => 'Nouveaux signalements',
            'open' => 'Signalements ouverts',
            'resolved' => 'Signalements resolus',
        ],
        'hotspots' => [
            'neighborhoods' => 'Quartiers principaux',
            'zones' => 'Zones principales',
            'none' => 'Aucune donnee pour cette periode.',
        ],
        'footer' => 'Ce digest est genere automatiquement par les operations NidVite.',
        'signature' => 'Cordialement,',
    ],
];
