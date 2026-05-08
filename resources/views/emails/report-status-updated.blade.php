@php($locale = $locale ?? 'fr')

<x-mail::message>
# {{ __('email.status_updated.greeting', ['uuid' => $report->public_tracking_id], $locale) }}

{{ __('email.status_updated.body', [
    'old' => __("status.{$oldStatus}", [], $locale),
    'new' => __("status.{$report->status}", [], $locale),
], $locale) }}

@if($report->status === 'rejected' && $report->rejection_reason)
**{{ __('email.status_updated.rejection_reason', [], $locale) }}:** {{ $report->rejection_reason }}
@endif

<x-mail::panel>
**{{ __('tracking.Numéro', [], $locale) }}:** {{ $report->public_tracking_id }}<br>
**{{ __('tracking.Date', [], $locale) }}:** {{ $report->created_at->translatedFormat('j F Y') }}<br>
**{{ __('tracking.Statut actuel', [], $locale) }}:** {{ __("status.{$report->status}", [], $locale) }}
</x-mail::panel>

<x-mail::button :url="route('report.tracking', $report->public_tracking_id)">
{{ __('email.status_updated.track_button', [], $locale) }}
</x-mail::button>

{{ __('email.status_updated.footer', [], $locale) }}

{{ __('email.status_updated.signature', [], $locale) }}<br>
**{{ config('app.name') }}**
</x-mail::message>
