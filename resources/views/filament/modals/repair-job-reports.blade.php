<div style="margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
    <h3 style="font-size: 0.95rem; font-weight: 600; color: #111827; margin: 0 0 10px;">
        Associated Reports ({{ $job->reports->count() }})
    </h3>

    @if($job->reports->isEmpty())
        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">No reports linked to this job.</p>
    @else
        <div style="max-height: 240px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
            @foreach($job->reports as $report)
                <div style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6;">
                    <p style="font-size: 0.8rem; color: #6b7280; margin: 0 0 4px;">{{ $report->uuid }}</p>
                    <p style="font-size: 0.875rem; color: #111827; margin: 0 0 4px;">{{ $report->address ?? 'Address not specified' }}</p>
                    <p style="font-size: 0.8rem; color: #6b7280; margin: 0;">{{ ucfirst(str_replace('_', ' ', $report->status)) }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
