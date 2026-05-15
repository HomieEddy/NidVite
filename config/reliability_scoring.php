<?php

return [
    'base_score' => 50,

    'weights' => [
        'road_pass_bonus' => 20,
        'road_fail_off_street_penalty' => -20,
        'road_fail_low_accuracy_penalty' => -12,
        'road_fail_both_penalty' => -30,

        'geofence_pass_bonus' => 15,
        'geofence_fail_penalty' => -20,

        'accuracy_pass_bonus' => 10,
        'accuracy_fail_penalty' => -15,

        'source_gps_bonus' => 8,
        'source_geocode_bonus' => 4,
        'source_manual_penalty' => -3,

        'not_spam_bonus' => 10,
        'spam_penalty' => -60,

        'description_rich_bonus' => 6,
        'description_ok_bonus' => 2,
        'description_short_penalty' => -5,
    ],
];
