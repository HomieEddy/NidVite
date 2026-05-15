<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Exports\ReportsDatasetExport;
use App\Filament\Resources\Reports\ReportResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export_excel')
                ->label(__('filament.admin.resources.reports.actions.export_excel'))
                ->icon('heroicon-o-table-cells')
                ->form([
                    DatePicker::make('start_date')
                        ->label(__('filament.admin.resources.reports.fields.start_date'))
                        ->default(now()->subDays(29)->toDateString())
                        ->required(),
                    DatePicker::make('end_date')
                        ->label(__('filament.admin.resources.reports.fields.end_date'))
                        ->default(now()->toDateString())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $startDate = Carbon::parse((string) $data['start_date'])->startOfDay();
                    $endDate = Carbon::parse((string) $data['end_date'])->endOfDay();

                    $rows = $this->buildReportsDataset($startDate, $endDate);
                    $filename = 'reports-dataset-'.$startDate->toDateString().'-'.$endDate->toDateString().'.xlsx';

                    return Excel::download(new ReportsDatasetExport($rows), $filename);
                }),
            Action::make('export_pdf')
                ->label(__('filament.admin.resources.reports.actions.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    DatePicker::make('start_date')
                        ->label(__('filament.admin.resources.reports.fields.start_date'))
                        ->default(now()->subDays(29)->toDateString())
                        ->required(),
                    DatePicker::make('end_date')
                        ->label(__('filament.admin.resources.reports.fields.end_date'))
                        ->default(now()->toDateString())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $startDate = Carbon::parse((string) $data['start_date'])->startOfDay();
                    $endDate = Carbon::parse((string) $data['end_date'])->endOfDay();

                    $rows = $this->buildReportsDataset($startDate, $endDate);
                    $filename = 'reports-dataset-'.$startDate->toDateString().'-'.$endDate->toDateString().'.pdf';

                    $pdf = Pdf::loadView('filament.exports.reports-dataset-pdf', [
                        'rows' => $rows,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ]);

                    return response()->streamDownload(static fn () => print ($pdf->output()), $filename);
                }),
        ];
    }

    private function buildReportsDataset(Carbon $startDate, Carbon $endDate)
    {
        return DB::table('reports')
            ->leftJoin('job_reports', 'job_reports.report_id', '=', 'reports.id')
            ->leftJoin('repair_jobs', 'repair_jobs.id', '=', 'job_reports.repair_job_id')
            ->leftJoin('expenses', 'expenses.repair_job_id', '=', 'repair_jobs.id')
            ->where('reports.is_spam', false)
            ->whereBetween('reports.created_at', [$startDate, $endDate])
            ->groupBy(
                'reports.id',
                'reports.public_tracking_id',
                'reports.status',
                'reports.priority',
                'reports.address',
                'reports.neighborhood',
                'reports.borough',
                'reports.created_at',
                'reports.completed_at'
            )
            ->selectRaw('reports.public_tracking_id as tracking_id')
            ->selectRaw('reports.status as status')
            ->selectRaw('reports.priority as priority')
            ->selectRaw('reports.address as address')
            ->selectRaw('reports.neighborhood as neighborhood')
            ->selectRaw('reports.borough as borough')
            ->selectRaw('reports.created_at as reported_at')
            ->selectRaw('reports.completed_at as completed_at')
            ->selectRaw('ROUND(COALESCE(SUM(expenses.total * (job_reports.cost_allocation_percentage / 100.0)), 0), 2) as allocated_cost_cad')
            ->orderByDesc('reports.created_at')
            ->get()
            ->map(function (object $row): array {
                return [
                    'tracking_id' => $row->tracking_id,
                    'status' => $row->status,
                    'priority' => $row->priority,
                    'address' => $row->address,
                    'neighborhood' => $row->neighborhood,
                    'borough' => $row->borough,
                    'reported_at' => $row->reported_at,
                    'completed_at' => $row->completed_at,
                    'allocated_cost_cad' => (float) $row->allocated_cost_cad,
                ];
            })
            ->values();
    }
}
