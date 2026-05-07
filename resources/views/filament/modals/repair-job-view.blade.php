<div style="width: 100%;">
    <div style="margin-bottom: 12px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="display: grid; grid-template-columns: 180px 1fr; border-bottom: 1px solid #f3f4f6;">
            <div style="padding: 10px 12px; font-size: 0.8rem; font-weight: 600; color: #374151; background: #f9fafb;">Field</div>
            <div style="padding: 10px 12px; font-size: 0.8rem; font-weight: 600; color: #374151; background: #f9fafb;">Value</div>
        </div>
        <div style="display: grid; grid-template-columns: 180px 1fr; border-bottom: 1px solid #f3f4f6;">
            <div style="padding: 10px 12px; font-size: 0.82rem; color: #6b7280;">Title</div>
            <div style="padding: 10px 12px; font-size: 0.9rem; color: #111827;"><strong>{{ $job->title }}</strong></div>
        </div>
        <div style="display: grid; grid-template-columns: 180px 1fr; border-bottom: 1px solid #f3f4f6;">
            <div style="padding: 10px 12px; font-size: 0.82rem; color: #6b7280;">Status</div>
            <div style="padding: 10px 12px; font-size: 0.9rem; color: #111827;">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</div>
        </div>
        <div style="display: grid; grid-template-columns: 180px 1fr;">
            <div style="padding: 10px 12px; font-size: 0.82rem; color: #6b7280;">Scheduled At</div>
            <div style="padding: 10px 12px; font-size: 0.9rem; color: #111827;">{{ optional($job->scheduled_at)->format('M j, Y H:i') ?? 'N/A' }}</div>
        </div>
    </div>

    <div style="margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
        <h3 style="font-size: 0.95rem; font-weight: 600; color: #111827; margin: 0 0 10px;">
            Linked Reports ({{ $job->reports->count() }})
        </h3>

        @if($job->reports->isEmpty())
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">No reports linked to this job.</p>
        @else
            <div style="max-height: 280px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                <div style="display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 12px; padding: 10px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <div style="font-size: 0.78rem; font-weight: 600; color: #374151;">Report UUID</div>
                    <div style="font-size: 0.78rem; font-weight: 600; color: #374151;">Address</div>
                    <div style="font-size: 0.78rem; font-weight: 600; color: #374151;">Status</div>
                    <div style="font-size: 0.78rem; font-weight: 600; color: #374151; text-align: right;">Action</div>
                </div>
                @foreach($job->reports as $report)
                    <div style="display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 12px; align-items: center; padding: 10px 12px; border-bottom: 1px solid #f3f4f6;">
                        <div style="font-size: 0.82rem; color: #6b7280;">{{ $report->uuid }}</div>
                        <div style="font-size: 0.875rem; color: #111827;">{{ $report->address ?? 'Address not specified' }}</div>
                        <div style="font-size: 0.82rem; color: #6b7280;">{{ ucfirst(str_replace('_', ' ', $report->status)) }}</div>
                        <a
                            href="{{ \App\Filament\Resources\Reports\ReportResource::getUrl('edit', ['record' => $report]) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            style="font-size: 0.8rem; color: #d97706; text-decoration: none; font-weight: 600; white-space: nowrap; text-align: right;"
                        >
                            Open Report
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
