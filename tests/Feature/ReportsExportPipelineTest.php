<?php

use App\Exports\ReportsDatasetExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

it('builds a valid excel export object for report datasets', function () {
    $rows = collect([
        [
            'tracking_id' => 'MTLAAAA1111',
            'status' => 'repaired',
            'priority' => 'normal',
            'address' => '123 Test St',
            'neighborhood' => 'Plateau-Mont-Royal',
            'borough' => 'Le Plateau-Mont-Royal',
            'reported_at' => now()->subDays(3)->toDateTimeString(),
            'completed_at' => now()->subDay()->toDateTimeString(),
            'allocated_cost_cad' => 120.50,
        ],
    ]);

    $export = new ReportsDatasetExport($rows);

    expect($export->headings())->toHaveCount(9)
        ->and($export->collection())->toBeInstanceOf(Collection::class)
        ->and($export->collection())->toHaveCount(1);
});

it('renders dashboard report dataset export as a valid pdf stream', function () {
    $startDate = Carbon::parse('2026-05-01')->startOfDay();
    $endDate = Carbon::parse('2026-05-07')->endOfDay();

    $rows = collect([
        [
            'tracking_id' => 'MTLBBBB2222',
            'status' => 'verified',
            'priority' => 'high',
            'address' => '456 Export Ave',
            'neighborhood' => 'Rosemont',
            'borough' => 'Rosemont-La Petite-Patrie',
            'reported_at' => now()->subDays(2)->toDateTimeString(),
            'completed_at' => null,
            'allocated_cost_cad' => 0,
        ],
    ]);

    $pdf = Pdf::loadView('filament.exports.reports-dataset-pdf', [
        'rows' => $rows,
        'startDate' => $startDate,
        'endDate' => $endDate,
    ]);

    $output = $pdf->output();

    expect($output)->not->toBe('')
        ->and(substr($output, 0, 4))->toBe('%PDF');
});
