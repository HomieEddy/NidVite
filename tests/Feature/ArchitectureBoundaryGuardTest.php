<?php

it('keeps report edit status changes routed through transition action', function () {
    $content = (string) file_get_contents(base_path('app/Filament/Resources/Reports/Pages/EditReport.php'));

    expect($content)
        ->toContain('TransitionReportStatusAction')
        ->toContain('handleRecordUpdate')
        ->toContain('app(TransitionReportStatusAction::class)');
});

it('keeps public report submission routed through SubmitReportAction boundary', function () {
    $content = (string) file_get_contents(base_path('resources/views/components/report-form.blade.php'));

    expect($content)
        ->toContain('SubmitReportAction')
        ->toContain('app(SubmitReportAction::class)');
});

it('keeps homepage query logic behind stats action boundary', function () {
    $content = (string) file_get_contents(base_path('app/Http/Controllers/HomeController.php'));

    expect($content)
        ->toContain('GetPublicReportStatsAction')
        ->toContain('$getPublicReportStats(');
});
