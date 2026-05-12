<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app/Actions/Reports',
        __DIR__.'/app/Filament/Resources/Reports/Schemas',
        __DIR__.'/app/Filament/Resources/RepairJobs/Schemas',
    ]);

    // Keep this gate non-disruptive while ensuring Rector runs in CI.
    $rectorConfig->skip([
        __DIR__.'/vendor/*',
    ]);

    $rectorConfig->rule(RemoveEmptyClassMethodRector::class);
};
