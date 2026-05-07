<?php

return [
    'ip_purge_days' => (int) env('RETENTION_IP_PURGE_DAYS', 30),

    'report_archive_days' => (int) env('RETENTION_REPORT_ARCHIVE_DAYS', 730),

    'cold_storage_disk' => env('RETENTION_COLD_STORAGE_DISK', 'r2-cold'),

    'cold_storage_prefix' => env('RETENTION_COLD_STORAGE_PREFIX', 'cold/reports'),
];
