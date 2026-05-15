@php($locale = $locale ?? 'fr')

<x-mail::message>
# {{ __('email.weekly_digest.greeting', [], $locale) }}

{{ __('email.weekly_digest.body', [
    'start' => $summary['window']['start'] ?? '',
    'end' => $summary['window']['end'] ?? '',
], $locale) }}

<x-mail::panel>
**{{ __('email.weekly_digest.counts.new', [], $locale) }}:** {{ (int) ($summary['counts']['new'] ?? 0) }}<br>
**{{ __('email.weekly_digest.counts.open', [], $locale) }}:** {{ (int) ($summary['counts']['open'] ?? 0) }}<br>
**{{ __('email.weekly_digest.counts.resolved', [], $locale) }}:** {{ (int) ($summary['counts']['resolved'] ?? 0) }}
</x-mail::panel>

## {{ __('email.weekly_digest.hotspots.neighborhoods', [], $locale) }}

@forelse(($summary['hotspots']['neighborhoods'] ?? []) as $item)
- {{ ($item['neighborhood'] ?? null) === 'UNKNOWN_NEIGHBORHOOD' ? __('email.weekly_digest.hotspots.unknown_neighborhood', [], $locale) : $item['neighborhood'] }}: {{ $item['count'] }}
@empty
- {{ __('email.weekly_digest.hotspots.none', [], $locale) }}
@endforelse

## {{ __('email.weekly_digest.hotspots.zones', [], $locale) }}

@forelse(($summary['hotspots']['zones'] ?? []) as $item)
- {{ __('tracking.eta_zone_' . ($item['zone'] ?? 'default'), [], $locale) }}: {{ $item['count'] }}
@empty
- {{ __('email.weekly_digest.hotspots.none', [], $locale) }}
@endforelse

{{ __('email.weekly_digest.footer', [], $locale) }}

{{ __('email.weekly_digest.signature', [], $locale) }}<br>
**{{ config('app.name') }}**
</x-mail::message>
