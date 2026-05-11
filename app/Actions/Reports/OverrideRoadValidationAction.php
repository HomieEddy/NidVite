<?php

namespace App\Actions\Reports;

use App\Models\Report;

class OverrideRoadValidationAction
{
    public function __invoke(Report $report, string $decision, string $auditNote): void
    {
        $report->overrideRoadValidation($decision, $auditNote);
    }
}
