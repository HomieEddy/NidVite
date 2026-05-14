<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Exports\ReportsDatasetExport;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\ReportSavedView;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('save_view')
                ->label(__('filament.admin.resources.reports.saved_views.actions.save'))
                ->icon('heroicon-o-bookmark')
                ->form([
                    TextInput::make('name')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.name'))
                        ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                        ->minLength(1)
                        ->maxLength(100)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->handleSavedViewOperation([
                        'operation' => 'save',
                        'name' => $data['name'] ?? null,
                    ]);
                }),
            Action::make('update_view')
                ->label(__('filament.admin.resources.reports.saved_views.actions.update'))
                ->icon('heroicon-o-pencil-square')
                ->form([
                    Select::make('view_id')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.view'))
                        ->options(fn (): array => ReportSavedView::query()
                            ->where('user_id', Auth::id())
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('name')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.rename_to'))
                        ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                        ->maxLength(100),
                ])
                ->action(function (array $data): void {
                    $this->handleSavedViewOperation([
                        'operation' => 'update',
                        'view_id' => $data['view_id'] ?? null,
                        'rename_to' => $data['name'] ?? null,
                    ]);
                }),
            Action::make('load_view')
                ->label(__('filament.admin.resources.reports.saved_views.actions.load'))
                ->icon('heroicon-o-folder-open')
                ->form([
                    Select::make('view_id')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.view'))
                        ->options(fn (): array => ReportSavedView::query()
                            ->where('user_id', Auth::id())
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->handleSavedViewOperation([
                        'operation' => 'load',
                        'view_id' => $data['view_id'] ?? null,
                    ]);
                }),
            Action::make('delete_view')
                ->label(__('filament.admin.resources.reports.saved_views.actions.delete'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Select::make('view_id')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.view'))
                        ->options(fn (): array => ReportSavedView::query()
                            ->where('user_id', Auth::id())
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->handleSavedViewOperation([
                        'operation' => 'delete',
                        'view_id' => $data['view_id'] ?? null,
                    ]);
                }),
            Action::make('saved_views')
                ->label(__('filament.admin.resources.reports.saved_views.actions.menu'))
                ->icon('heroicon-o-bookmark-square')
                ->authorize(fn (): bool => Auth::check())
                ->slideOver()
                ->form([
                    Select::make('operation')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.operation'))
                        ->options([
                            'save' => __('filament.admin.resources.reports.saved_views.actions.save'),
                            'update' => __('filament.admin.resources.reports.saved_views.actions.update'),
                            'load' => __('filament.admin.resources.reports.saved_views.actions.load'),
                            'delete' => __('filament.admin.resources.reports.saved_views.actions.delete'),
                        ])
                        ->default('save')
                        ->live()
                        ->required(),
                    Select::make('confirm_delete')
                        ->label('Confirm delete')
                        ->options([
                            'no' => 'No',
                            'yes' => 'Yes',
                        ])
                        ->default('no')
                        ->required(fn (callable $get): bool => $get('operation') === 'delete')
                        ->visible(fn (callable $get): bool => $get('operation') === 'delete'),
                    TextInput::make('name')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.name'))
                        ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                        ->minLength(1)
                        ->maxLength(100)
                        ->required(fn (callable $get): bool => $get('operation') === 'save')
                        ->visible(fn (callable $get): bool => in_array($get('operation'), ['save', 'update'], true)),
                    Select::make('view_id')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.view'))
                        ->options(fn (): array => ReportSavedView::query()
                            ->where('user_id', Auth::id())
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(fn (callable $get): bool => in_array($get('operation'), ['update', 'load', 'delete'], true))
                        ->visible(fn (callable $get): bool => in_array($get('operation'), ['update', 'load', 'delete'], true)),
                    TextInput::make('rename_to')
                        ->label(__('filament.admin.resources.reports.saved_views.fields.rename_to'))
                        ->dehydrateStateUsing(fn ($state): string => trim((string) $state))
                        ->maxLength(100)
                        ->visible(fn (callable $get): bool => $get('operation') === 'update'),
                ])
                ->action(function (array $data): void {
                    $this->handleSavedViewOperation($data);
                }),
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleSavedViewOperation(array $data): void
    {
        $operation = (string) ($data['operation'] ?? 'save');

        if ($operation === 'save') {
            $userId = Auth::id();
            if (! $userId) {
                return;
            }

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                Notification::make()
                    ->danger()
                    ->title('Invalid view name.')
                    ->send();

                return;
            }

            ReportSavedView::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'name' => $name,
                ],
                $this->currentSavedViewPayload()
            );

            Notification::make()
                ->success()
                ->title(__('filament.admin.resources.reports.saved_views.feedback.saved'))
                ->send();

            return;
        }

        if ($operation === 'update') {
            $view = ReportSavedView::query()
                ->where('user_id', Auth::id())
                ->find($data['view_id'] ?? null);

            if (! $view) {
                Notification::make()
                    ->danger()
                    ->title('Saved view not found.')
                    ->send();

                return;
            }

            $payload = $this->currentSavedViewPayload();
            $newName = trim((string) ($data['rename_to'] ?? ''));
            if ($newName !== '') {
                if (mb_strlen($newName) > 100) {
                    Notification::make()
                        ->danger()
                        ->title('View name is too long.')
                        ->send();

                    return;
                }

                $nameExists = ReportSavedView::query()
                    ->where('user_id', (int) Auth::id())
                    ->where('name', $newName)
                    ->whereKeyNot($view->id)
                    ->exists();

                if ($nameExists) {
                    Notification::make()
                        ->danger()
                        ->title('A saved view with this name already exists.')
                        ->send();

                    return;
                }

                $payload['name'] = $newName;
            }

            $view->update($payload);

            Notification::make()
                ->success()
                ->title(__('filament.admin.resources.reports.saved_views.feedback.updated'))
                ->send();

            return;
        }

        if ($operation === 'load') {
            $view = ReportSavedView::query()
                ->where('user_id', Auth::id())
                ->find($data['view_id'] ?? null);

            if (! $view) {
                Notification::make()
                    ->danger()
                    ->title('Saved view not found.')
                    ->send();

                return;
            }

            $this->applySavedView($view);

            Notification::make()
                ->success()
                ->title(__('filament.admin.resources.reports.saved_views.feedback.loaded'))
                ->send();

            return;
        }

        if ($operation === 'delete') {
            if (($data['confirm_delete'] ?? 'no') !== 'yes') {
                Notification::make()
                    ->danger()
                    ->title('Delete confirmation is required.')
                    ->send();

                return;
            }

            ReportSavedView::query()
                ->where('user_id', Auth::id())
                ->whereKey($data['view_id'] ?? null)
                ->delete();

            Notification::make()
                ->success()
                ->title(__('filament.admin.resources.reports.saved_views.feedback.deleted'))
                ->send();
        }
    }

    /**
     * @return array{filters: array<string, mixed>, sort_column: ?string, sort_direction: ?string, search: ?string}
     */
    private function currentSavedViewPayload(): array
    {
        return [
            'filters' => is_array($this->tableFilters ?? null) ? $this->tableFilters : [],
            'sort_column' => $this->getTableSortColumn(),
            'sort_direction' => $this->getTableSortDirection(),
            'search' => is_string($this->tableSearch ?? null) ? $this->tableSearch : null,
        ];
    }

    private function applySavedView(ReportSavedView $view): void
    {
        $this->tableFilters = is_array($view->filters) ? $view->filters : [];
        $this->sortTable($view->sort_column, $view->sort_direction);
        $this->tableSearch = $view->search;
        $this->resetPage();
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
