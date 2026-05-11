<?php

return [
    'mode' => env('REPORT_VALIDATION_MODE', 'shadow'),
    'max_road_distance_meters' => (int) env('REPORT_VALIDATION_MAX_ROAD_DISTANCE_METERS', 35),
    'near_miss_buffer_meters' => (int) env('REPORT_VALIDATION_NEAR_MISS_BUFFER_METERS', 10),
    'road_search_radius_meters' => (int) env('REPORT_VALIDATION_ROAD_SEARCH_RADIUS_METERS', 250),
    'max_location_accuracy_meters' => (int) env('REPORT_VALIDATION_MAX_LOCATION_ACCURACY_METERS', 50),
];
