<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Actions\Reports\OverrideRoadValidationAction;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('filament.admin.resources.reports.empty_state.heading'))
            ->emptyStateDescription(__('filament.admin.resources.reports.empty_state.description'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('filament.admin.resources.reports.actions.create'))
                    ->url(ReportResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->visible(fn (): bool => Auth::user()?->can('create', Report::class) ?? false),
            ])
            ->columns([
                TextColumn::make('public_tracking_id')
                    ->label(__('filament.admin.resources.reports.fields.tracking_id'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reporter_email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('neighborhood')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('borough')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'gray',
                        'verified' => 'info',
                        'scheduled' => 'warning',
                        'in_progress' => 'amber',
                        'repaired' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('reliability_score')
                    ->label(__('filament.admin.resources.reports.fields.reliability_score'))
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : (string) $state)
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 80 => 'success',
                        $state >= 60 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('road_validation_decision')
                    ->label(__('filament.admin.resources.reports.fields.road_validation_status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pass' => __('filament.admin.resources.reports.validation_decisions.pass'),
                        'fail_off_street' => __('filament.admin.resources.reports.validation_decisions.fail_off_street'),
                        'fail_low_accuracy' => __('filament.admin.resources.reports.validation_decisions.fail_low_accuracy'),
                        'fail_both' => __('filament.admin.resources.reports.validation_decisions.fail_both'),
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pass' => 'success',
                        'fail_off_street', 'fail_low_accuracy', 'fail_both' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('road_distance_meters')
                    ->label(__('filament.admin.resources.reports.fields.road_distance'))
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '-' : number_format($state, 1).' m')
                    ->sortable(),
                TextColumn::make('location')
                    ->label(__('filament.admin.resources.reports.fields.map'))
                    ->icon('heroicon-m-map-pin')
                    ->color('amber')
                    ->formatStateUsing(fn () => __('filament.admin.resources.reports.actions.view_on_map'))
                    ->action(
                        Action::make('viewLocation')
                            ->label(__('filament.admin.resources.reports.actions.view_on_map'))
                            ->modalHeading(__('filament.admin.resources.reports.actions.report_location'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel(__('filament.admin.resources.reports.actions.close'))
                            ->modalContent(function ($record): View {
                                $location = $record->coordinatePoint();

                                return view('filament.modals.report-location', [
                                    'report' => $record,
                                    'location' => $location,
                                ]);
                            })
                    )
                    ->tooltip(__('filament.admin.resources.reports.tooltips.map')),
                IconColumn::make('geofence_passed')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament.admin.resources.reports.filters.status'))
                    ->options([
                        'received' => __('filament.admin.resources.reports.statuses.received'),
                        'verified' => __('filament.admin.resources.reports.statuses.verified'),
                        'scheduled' => __('filament.admin.resources.reports.statuses.scheduled'),
                        'in_progress' => __('filament.admin.resources.reports.statuses.in_progress'),
                        'repaired' => __('filament.admin.resources.reports.statuses.repaired'),
                        'rejected' => __('filament.admin.resources.reports.statuses.rejected'),
                    ])
                    ->multiple(),
                SelectFilter::make('priority')
                    ->label(__('filament.admin.resources.reports.filters.priority'))
                    ->options([
                        'low' => __('filament.admin.resources.reports.priorities.low'),
                        'normal' => __('filament.admin.resources.reports.priorities.normal'),
                        'high' => __('filament.admin.resources.reports.priorities.high'),
                        'critical' => __('filament.admin.resources.reports.priorities.critical'),
                    ])
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->groups([
                Group::make('status')
                    ->label(__('filament.admin.resources.reports.groups.status'))
                    ->getTitleFromRecordUsing(fn ($record) => __('filament.admin.resources.reports.statuses.'.$record->status)),
                Group::make('neighborhood')
                    ->label(__('filament.admin.resources.reports.groups.neighborhood')),
                Group::make('borough')
                    ->label(__('filament.admin.resources.reports.groups.borough')),
                Group::make('priority')
                    ->label(__('filament.admin.resources.reports.groups.priority'))
                    ->getTitleFromRecordUsing(fn ($record) => __('filament.admin.resources.reports.priorities.'.$record->priority)),
                Group::make('created_at')
                    ->label(__('filament.admin.resources.reports.groups.date'))
                    ->getTitleFromRecordUsing(fn ($record) => $record->created_at->format('M Y')),
            ])
            ->defaultGroup('status')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('verify')
                    ->label(__('filament.admin.resources.reports.actions.verify'))
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Report $record): bool => (Auth::user()?->can('update', $record) ?? false)
                        && $record->canTransitionTo('verified'))
                    ->authorize(fn (Report $record): bool => Auth::user()?->can('update', $record) ?? false)
                    ->action(function (Report $record): void {
                        if (! (Auth::user()?->can('update', $record) ?? false) || ! $record->canTransitionTo('verified')) {
                            Notification::make()
                                ->danger()
                                ->title('This report can no longer be verified.')
                                ->send();

                            return;
                        }

                        $record->transitionTo('verified');
                    }),
                Action::make('reject')
                    ->label(__('filament.admin.resources.reports.actions.reject'))
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Report $record): bool => (Auth::user()?->can('update', $record) ?? false)
                        && $record->canTransitionTo('rejected'))
                    ->authorize(fn (Report $record): bool => Auth::user()?->can('update', $record) ?? false)
                    ->form([
                        Textarea::make('reason')
                            ->label(__('filament.admin.fields_common.rejection_reason'))
                            ->maxLength(500),
                    ])
                    ->action(function (Report $record, array $data): void {
                        if (! (Auth::user()?->can('update', $record) ?? false) || ! $record->canTransitionTo('rejected')) {
                            Notification::make()
                                ->danger()
                                ->title('This report can no longer be rejected.')
                                ->send();

                            return;
                        }

                        $reason = trim((string) ($data['reason'] ?? ''));

                        $record->transitionTo('rejected', $reason !== '' ? $reason : null);
                    }),
                Action::make('override_validation')
                    ->label(__('filament.admin.resources.reports.actions.override_validation'))
                    ->icon('heroicon-m-shield-check')
                    ->visible(fn (Report $record): bool => Auth::user()?->can('update', $record) ?? false)
                    ->authorize(fn (Report $record): bool => Auth::user()?->can('update', $record) ?? false)
                    ->form([
                        Select::make('decision')
                            ->label(__('filament.admin.resources.reports.fields.override_decision'))
                            ->options([
                                'pass' => __('filament.admin.resources.reports.validation_decisions.pass'),
                                'fail_off_street' => __('filament.admin.resources.reports.validation_decisions.fail_off_street'),
                                'fail_low_accuracy' => __('filament.admin.resources.reports.validation_decisions.fail_low_accuracy'),
                                'fail_both' => __('filament.admin.resources.reports.validation_decisions.fail_both'),
                            ])
                            ->required(),
                        Textarea::make('audit_note')
                            ->label(__('filament.admin.resources.reports.fields.audit_note'))
                            ->required()
                            ->minLength(5),
                    ])
                    ->action(function (Report $record, array $data): void {
                        app(OverrideRoadValidationAction::class)(
                            $record,
                            (string) $data['decision'],
                            (string) $data['audit_note']
                        );
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('duplicate_close')
                        ->label(__('filament.admin.resources.reports.bulk_actions.duplicate_close.label'))
                        ->icon('heroicon-o-no-symbol')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data): void {
                            $user = Auth::user();
                            if (! $user) {
                                return;
                            }

                            $reason = trim((string) ($data['reason'] ?? ''));
                            $rejectionReason = $reason !== ''
                                ? $reason
                                : __('filament.admin.resources.reports.bulk_actions.duplicate_close.default_reason');

                            $recordIds = $records->modelKeys();
                            $parent = activity('report_batch')
                                ->causedBy($user)
                                ->withProperties([
                                    'operation' => 'duplicate_close',
                                    'selected_report_ids' => $recordIds,
                                ])
                                ->log('Batch duplicate-close started');
                            $parentId = $parent instanceof ActivityModel ? $parent->id : null;

                            $processed = 0;
                            $blocked = [];

                            foreach ($records as $record) {
                                if (! $record instanceof Report) {
                                    continue;
                                }

                                if (! $user->can('update', $record)) {
                                    $blocked[] = $record->public_tracking_id;
                                    activity('report_batch_item')
                                        ->causedBy($user)
                                        ->performedOn($record)
                                        ->withProperties([
                                            'batch_activity_id' => $parentId,
                                            'operation' => 'duplicate_close',
                                            'result' => 'blocked',
                                            'reason' => 'unauthorized',
                                            'old_status' => $record->status,
                                            'new_status' => $record->status,
                                        ])
                                        ->log('Batch duplicate-close blocked');

                                    continue;
                                }

                                if (! $record->canTransitionTo('rejected')) {
                                    $blocked[] = $record->public_tracking_id;
                                    activity('report_batch_item')
                                        ->causedBy($user)
                                        ->performedOn($record)
                                        ->withProperties([
                                            'batch_activity_id' => $parentId,
                                            'operation' => 'duplicate_close',
                                            'result' => 'blocked',
                                            'reason' => 'invalid_transition',
                                            'old_status' => $record->status,
                                            'new_status' => $record->status,
                                        ])
                                        ->log('Batch duplicate-close blocked');

                                    continue;
                                }

                                $oldStatus = $record->status;
                                $oldRejectionReason = $record->rejection_reason;
                                $record->transitionTo('rejected', $rejectionReason);
                                $record->refresh();

                                activity('report_batch_item')
                                    ->causedBy($user)
                                    ->performedOn($record)
                                    ->withProperties([
                                        'batch_activity_id' => $parentId,
                                        'operation' => 'duplicate_close',
                                        'result' => 'processed',
                                        'old_status' => $oldStatus,
                                        'new_status' => $record->status,
                                        'old_rejection_reason' => $oldRejectionReason,
                                        'new_rejection_reason' => $rejectionReason,
                                    ])
                                    ->log('Batch duplicate-close processed');

                                $processed++;
                            }

                            if ($parent instanceof ActivityModel) {
                                $parent->properties = $parent->properties->merge([
                                    'processed_count' => $processed,
                                    'blocked_count' => count($blocked),
                                    'blocked_tracking_ids' => $blocked,
                                ]);
                                $parent->save();
                            }

                            Notification::make()
                                ->title(__('filament.admin.resources.reports.bulk_actions.feedback.summary', [
                                    'processed' => $processed,
                                    'blocked' => count($blocked),
                                ]))
                                ->success()
                                ->send();
                        })
                        ->form([
                            Textarea::make('reason')
                                ->label(__('filament.admin.resources.reports.bulk_actions.duplicate_close.reason'))
                                ->maxLength(500),
                        ]),
                    BulkAction::make('assign_contractor')
                        ->label(__('filament.admin.resources.reports.bulk_actions.assign_contractor.label'))
                        ->icon('heroicon-o-user-plus')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->form([
                            Select::make('contractor_user_id')
                                ->label(__('filament.admin.resources.reports.bulk_actions.assign_contractor.contractor'))
                                ->options(fn (): array => User::query()
                                    ->where('is_active', true)
                                    ->whereHas('role', fn ($query) => $query->where('slug', 'service_worker'))
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $user = Auth::user();
                            if (! $user) {
                                return;
                            }

                            $contractorId = (int) ($data['contractor_user_id'] ?? 0);
                            if ($contractorId < 1) {
                                return;
                            }

                            $isEligibleContractor = User::query()
                                ->whereKey($contractorId)
                                ->where('is_active', true)
                                ->whereHas('role', fn ($query) => $query->where('slug', 'service_worker'))
                                ->exists();

                            if (! $isEligibleContractor) {
                                Notification::make()
                                    ->title(__('filament.admin.resources.reports.bulk_actions.assign_contractor.invalid_contractor'))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $parent = activity('report_batch')
                                ->causedBy($user)
                                ->withProperties([
                                    'operation' => 'assign_contractor',
                                    'selected_report_ids' => $records->modelKeys(),
                                    'contractor_user_id' => $contractorId,
                                ])
                                ->log('Batch assign-contractor started');
                            $parentId = $parent instanceof ActivityModel ? $parent->id : null;

                            $processed = 0;
                            $blocked = [];

                            foreach ($records as $record) {
                                if (! $record instanceof Report) {
                                    continue;
                                }

                                if (! $user->can('update', $record)) {
                                    $blocked[] = $record->public_tracking_id;
                                    activity('report_batch_item')
                                        ->causedBy($user)
                                        ->performedOn($record)
                                        ->withProperties([
                                            'batch_activity_id' => $parentId,
                                            'operation' => 'assign_contractor',
                                            'result' => 'blocked',
                                            'reason' => 'unauthorized',
                                            'old_status' => $record->status,
                                            'new_status' => $record->status,
                                        ])
                                        ->log('Batch assign-contractor blocked');

                                    continue;
                                }

                                $oldStatus = $record->status;
                                if ($record->status === 'verified') {
                                    $record->transitionTo('scheduled');
                                } elseif (! in_array($record->status, ['scheduled', 'in_progress'], true)) {
                                    $blocked[] = $record->public_tracking_id;
                                    activity('report_batch_item')
                                        ->causedBy($user)
                                        ->performedOn($record)
                                        ->withProperties([
                                            'batch_activity_id' => $parentId,
                                            'operation' => 'assign_contractor',
                                            'result' => 'blocked',
                                            'reason' => 'invalid_transition',
                                            'old_status' => $oldStatus,
                                            'new_status' => $record->status,
                                        ])
                                        ->log('Batch assign-contractor blocked');

                                    continue;
                                }

                                $oldContractorId = $record->contractor_user_id;
                                $record->forceFill(['contractor_user_id' => $contractorId])->save();

                                activity('report_batch_item')
                                    ->causedBy($user)
                                    ->performedOn($record)
                                    ->withProperties([
                                        'batch_activity_id' => $parentId,
                                        'operation' => 'assign_contractor',
                                        'result' => 'processed',
                                        'old_status' => $oldStatus,
                                        'new_status' => $record->status,
                                        'old_contractor_user_id' => $oldContractorId,
                                        'new_contractor_user_id' => $contractorId,
                                    ])
                                    ->log('Batch assign-contractor processed');

                                $processed++;
                            }

                            if ($parent instanceof ActivityModel) {
                                $parent->properties = $parent->properties->merge([
                                    'processed_count' => $processed,
                                    'blocked_count' => count($blocked),
                                    'blocked_tracking_ids' => $blocked,
                                ]);
                                $parent->save();
                            }

                            Notification::make()
                                ->title(__('filament.admin.resources.reports.bulk_actions.feedback.summary', [
                                    'processed' => $processed,
                                    'blocked' => count($blocked),
                                ]))
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('request_more_info')
                        ->label(__('filament.admin.resources.reports.bulk_actions.request_more_info.label'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->form([
                            Textarea::make('note')
                                ->label(__('filament.admin.resources.reports.bulk_actions.request_more_info.note'))
                                ->required()
                                ->minLength(5)
                                ->maxLength(1000),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $user = Auth::user();
                            if (! $user) {
                                return;
                            }

                            $note = trim((string) ($data['note'] ?? ''));
                            if ($note === '') {
                                return;
                            }

                            $parent = activity('report_batch')
                                ->causedBy($user)
                                ->withProperties([
                                    'operation' => 'request_more_info',
                                    'selected_report_ids' => $records->modelKeys(),
                                    'note_length' => mb_strlen($note),
                                ])
                                ->log('Batch request-more-info started');
                            $parentId = $parent instanceof ActivityModel ? $parent->id : null;

                            $processed = 0;
                            $blocked = [];

                            foreach ($records as $record) {
                                if (! $record instanceof Report) {
                                    continue;
                                }

                                if (! $user->can('update', $record)) {
                                    $blocked[] = $record->public_tracking_id;
                                    activity('report_batch_item')
                                        ->causedBy($user)
                                        ->performedOn($record)
                                        ->withProperties([
                                            'batch_activity_id' => $parentId,
                                            'operation' => 'request_more_info',
                                            'result' => 'blocked',
                                            'reason' => 'unauthorized',
                                            'old_status' => $record->status,
                                            'new_status' => $record->status,
                                        ])
                                        ->log('Batch request-more-info blocked');

                                    continue;
                                }

                                if ($record->isTerminal()) {
                                    $blocked[] = $record->public_tracking_id;
                                    activity('report_batch_item')
                                        ->causedBy($user)
                                        ->performedOn($record)
                                        ->withProperties([
                                            'batch_activity_id' => $parentId,
                                            'operation' => 'request_more_info',
                                            'result' => 'blocked',
                                            'reason' => 'terminal_state',
                                            'old_status' => $record->status,
                                            'new_status' => $record->status,
                                        ])
                                        ->log('Batch request-more-info blocked');

                                    continue;
                                }

                                $oldNotes = $record->admin_notes;
                                $entry = '['.now()->toIso8601String().'] REQUEST_MORE_INFO: '.$note;
                                $record->forceFill([
                                    'admin_notes' => trim((string) ($oldNotes ? $oldNotes.PHP_EOL.$entry : $entry)),
                                ])->save();

                                activity('report_batch_item')
                                    ->causedBy($user)
                                    ->performedOn($record)
                                    ->withProperties([
                                        'batch_activity_id' => $parentId,
                                        'operation' => 'request_more_info',
                                        'result' => 'processed',
                                        'old_status' => $record->status,
                                        'new_status' => $record->status,
                                        'old_admin_notes_length' => mb_strlen((string) $oldNotes),
                                        'new_admin_notes_length' => mb_strlen((string) $record->admin_notes),
                                    ])
                                    ->log('Batch request-more-info processed');

                                $processed++;
                            }

                            if ($parent instanceof ActivityModel) {
                                $parent->properties = $parent->properties->merge([
                                    'processed_count' => $processed,
                                    'blocked_count' => count($blocked),
                                    'blocked_tracking_ids' => $blocked,
                                ]);
                                $parent->save();
                            }

                            Notification::make()
                                ->title(__('filament.admin.resources.reports.bulk_actions.feedback.summary', [
                                    'processed' => $processed,
                                    'blocked' => count($blocked),
                                ]))
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
